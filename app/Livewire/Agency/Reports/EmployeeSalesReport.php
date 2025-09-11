<?php

namespace App\Livewire\Agency\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Sale;
use App\Models\User;
use App\Models\Provider;
use App\Models\DynamicListItem;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\CommissionProfile;
use App\Models\CommissionEmployeeRateOverride;
use App\Models\EmployeeMonthlyTarget;
use App\Models\WalletTransaction;
use App\Models\Collection;
use Illuminate\Support\Str;

#[Layout('layouts.agency')]
class EmployeeSalesReport extends Component
{
    use WithPagination;

    // فلاتر عامة
    public $employeeId = '';
    public $serviceTypeFilter = '';
    public $providerFilter = '';
    public $startDate = '';
    public $endDate = '';
    public $search = '';
    public $viewType = 'summary'; // summary | details

    // ترتيب جدول العمليات
    public $sortField = 'sale_date';
    public $sortDirection = 'desc';

    // قوائم
    public $employees = [];
    public $serviceTypes = [];
    public $providers = [];

    // ملخص
    public $totals = [
        'count' => 0,
        'sell' => 0,
        'buy' => 0,
        'profit' => 0,
        'commission' => 0,
        'remaining' => 0,
        'employee_commission_expected' => 0,
    ];

    // التفصيلي
    public ?string $drillType = null;   // 'service' | 'month' | null
    public ?string $drillValue = null;  // service_type_id أو 'YYYY-MM'

    protected $queryString = [
        'employeeId' => ['except' => ''],
        'serviceTypeFilter' => ['except' => ''],
        'providerFilter' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'search' => ['except' => ''],
        'viewType' => ['except' => 'summary'],
        'sortField' => ['except' => 'sale_date'],
        'sortDirection' => ['except' => 'desc'],
        'drillType' => ['except' => null],
        'drillValue' => ['except' => null],
    ];

    public function mount()
    {
        $agencyId = Auth::user()->agency_id;

        $this->employeeId = request()->query('employeeId', $this->employeeId);
        if ($this->employeeId) {
            $this->viewType = 'details';
        }

        $this->employees = User::where('agency_id', $agencyId)->orderBy('name')->get();

        $this->serviceTypes = DynamicListItem::whereHas('list', function ($q) {
            $q->where('name', 'قائمة الخدمات')
                ->where(function ($qq) {
                    $qq->where('created_by_agency', auth()->user()->agency_id)
                        ->orWhereNull('created_by_agency');
                });
        })->orderBy('order')->get();

        $this->providers = Provider::where('agency_id', $agencyId)->orderBy('name')->get();
    }

    // أزرار
    public function showEmployeeDetails($id)
    {
        $this->employeeId = $id;
        $this->viewType = 'details';
        $this->resetPage();
    }

    public function backToEmployees()
    {
        $this->reset(['employeeId', 'drillType', 'drillValue']);
        $this->viewType = 'summary';
        $this->resetPage();
    }

    public function setDrill(string $type, string $value): void
    {
        $this->drillType = $type;
        $this->drillValue = $value;
        $this->resetPage();
    }

    public function clearDrill(): void
    {
        $this->drillType = null;
        $this->drillValue = null;
        $this->resetPage();
    }

    // ========= نسب وقطعيات =========
    protected function employeeCommissionRate(?User $user): float
    {
        if (!$user)
            return 20.0;

        if (!is_null($user->commission_rate) && $user->commission_rate > 0) {
            return (float) $user->commission_rate;
        }
        if (!is_null($user->commission_percentage) && $user->commission_percentage > 0) {
            return (float) $user->commission_percentage;
        }

        $profile = CommissionProfile::where('agency_id', $user->agency_id)
            ->where('is_active', 1)->first();

        if ($profile) {
            $override = CommissionEmployeeRateOverride::where('profile_id', $profile->id)
                ->where('user_id', $user->id)
                ->value('rate');

            if (!is_null($override) && $override > 0) {
                return (float) $override;
            }

            if (!is_null($profile->employee_rate) && $profile->employee_rate > 0) {
                return (float) $profile->employee_rate;
            }
        }

        return 20.0;
    }

    protected function effectiveCustomerCommission($sale): float
    {
        $base = (float) ($sale->commission ?? 0);
        if ($sale->status === 'Refund-Full')
            return 0.0;

        $refundedCommission = (float) ($sale->refunded_commission ?? 0);
        if ($refundedCommission > 0)
            return max(0.0, $base - $refundedCommission);

        $refundedAmount = (float) ($sale->refunded_amount ?? 0);
        $sell = (float) ($sale->usd_sell ?? 0);

        if ($sale->status === 'Refund-Partial' && $sell > 0 && $refundedAmount > 0) {
            $ratio = max(0.0, min(1.0, ($sell - $refundedAmount) / $sell));
            return round($base * $ratio, 2);
        }

        return $base;
    }

    protected function profitParts($sale): array
    {
        $sell = (float) ($sale->usd_sell ?? 0);
        $buy = (float) ($sale->usd_buy ?? 0);
        $baseProfit = $sell - $buy;

        $netSell = $sale->status === 'Refund-Full'
            ? 0.0
            : max(0.0, $sell - (float) ($sale->refunded_amount ?? 0));

        $netProfit = ($sell > 0) ? round($baseProfit * ($netSell / $sell), 2) : 0.0;

        $collected = (float) ($sale->amount_paid ?? 0);
        $collected += isset($sale->collections_sum_amount)
            ? (float) $sale->collections_sum_amount
            : (float) $sale->collections->sum('amount');

        $collected = min($collected, $netSell);
        $collectRatio = ($netSell > 0) ? min(1.0, $collected / $netSell) : 0.0;
        $collectedProfit = round($netProfit * $collectRatio, 2);

        return [
            'sell' => $sell,
            'buy' => $buy,
            'base_profit' => $baseProfit,
            'net_sell' => $netSell,
            'net_profit' => $netProfit,
            'collected' => $collected,
            'collected_profit' => $collectedProfit,
        ];
    }
    // =================================

    protected function baseQuery()
    {
        $user = Auth::user();
        $agency = $user->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        return Sale::with(['user', 'service', 'provider', 'customer', 'collections'])
            ->whereIn('agency_id', $agencyIds)
            ->when($this->employeeId, fn($q) => $q->where('user_id', $this->employeeId))
            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when(
                $this->startDate && $this->endDate,
                fn($q) =>
                $q->whereBetween('sale_date', [$this->startDate, $this->endDate])
            )
            ->when(
                $this->startDate && !$this->endDate,
                fn($q) =>
                $q->whereDate('sale_date', '>=', $this->startDate)
            )
            ->when(
                !$this->startDate && $this->endDate,
                fn($q) =>
                $q->whereDate('sale_date', '<=', $this->endDate)
            )
            ->when($this->search, function ($q) {
                $term = "%{$this->search}%";
                $q->where(function ($qq) use ($term) {
                    $qq->where('beneficiary_name', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('pnr', 'like', $term);
                });
            });
    }

    protected function operationsQuery()
    {
        return $this->baseQuery()
            ->when(
                $this->drillType === 'service' && $this->drillValue,
                fn($q) =>
                $q->where('service_type_id', $this->drillValue)
            )
            ->when($this->drillType === 'month' && $this->drillValue, function ($q) {
                $start = Carbon::createFromFormat('Y-m', $this->drillValue)->startOfMonth()->toDateString();
                $end = Carbon::createFromFormat('Y-m', $this->drillValue)->endOfMonth()->toDateString();
                $q->whereBetween('sale_date', [$start, $end]);
            });
    }

    // ==== هدف الموظف حسب الأشهر الظاهرة ====
    protected function employeeTargetForRows(?User $employee, $rows): float
    {
        if (!$employee || !$rows || $rows->isEmpty()) {
            return (float) ($employee?->main_target ?? 0);
        }

        $ym = $rows->map(function ($s) {
            $d = Carbon::parse($s->sale_date);
            return ['y' => (int) $d->year, 'm' => (int) $d->month];
        })->unique(fn($a) => $a['y'] . '-' . $a['m'])->values();

        if ($ym->isEmpty()) {
            return (float) ($employee->main_target ?? 0);
        }

        // بناء استعلام ديناميكي (متوافق مع MySQL 5.7+)
        $q = EmployeeMonthlyTarget::where('user_id', $employee->id);
        $q->where(function ($outer) use ($ym) {
            foreach ($ym as $a) {
                $outer->orWhere(function ($qq) use ($a) {
                    $qq->where('year', $a['y'])->where('month', $a['m']);
                });
            }
        });
        $sum = (float) $q->sum('main_target');

        return $sum > 0 ? $sum : (float) ($employee->main_target ?? 0);
    }

    // إجمالي الدين مثل صفحة "تحصيلات الموظفين"
    protected function debtFromNetForRows($rows): float
    {
        if (!$rows || $rows->isEmpty())
            return 0.0;

        $sum = 0.0;
        $customerIds = $rows->pluck('customer_id')->unique()->filter();

        foreach ($customerIds as $cid) {
            $net = $this->netForCustomer((int) $cid); // نفس منطق صفحة التحصيلات
            if ($net < 0)
                $sum += abs($net);         // نجمع السوالب فقط كدين
        }

        return round($sum, 2);
    }


    // ملخص لكل موظف
    protected function perEmployeeRows()
    {
        $sales = $this->baseQuery()->with(['collections'])->withSum('collections', 'amount')->get();

        $grouped = $sales->groupBy('user_id')->map(function ($rows) {
            $sell = (float) $rows->sum('usd_sell');
            $buy = (float) $rows->sum('usd_buy');
            $profit = $sell - $buy;

            $customerCommission = (float) $rows->sum(fn($s) => $this->effectiveCustomerCommission($s));

            $user = $rows->first()?->user;

            $agg = $this->aggCommissionLikeIndex($rows, $user);

            $paid = (float) $rows->map(function ($s) {
                if (in_array($s->status, ['Refund-Full', 'Refund-Partial']))
                    return 0;
                return (float) ($s->amount_paid ?? 0) + (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
            })->sum();

            return [
                'user' => $user,
                'count' => $rows->count(),
                'sell' => $sell,
                'buy' => $buy,
                'profit' => $profit,
                'commission' => $customerCommission,
                'remaining' => $this->debtFromNetForRows($rows),
                'employee_commission_expected' => $agg['expected'],
            ];
        });

        return $grouped->sortBy(fn($r) => $r['user']?->name ?? '');
    }

    protected function computeTotals($sales)
    {
        $sell = (float) $sales->sum('usd_sell');
        $buy = (float) $sales->sum('usd_buy');
        $profit = $sell - $buy;

        $customerCommission = (float) $sales->sum(fn($s) => $this->effectiveCustomerCommission($s));

        $totalPaid = $sales->map(function ($sale) {
            if (in_array($sale->status, ['Refund-Full', 'Refund-Partial']))
                return 0;
            return (float) ($sale->amount_paid ?? 0)
                + (float) ($sale->collections_sum_amount ?? $sale->collections->sum('amount'));
        })->sum();
        $remaining = $this->debtFromNetForRows($sales);

        $empExpected = 0.0;
        $empDue = 0.0;

        if ($this->employeeId) {
            $user = User::find($this->employeeId);
            $agg = $this->aggCommissionLikeIndex($sales, $user);
            $empExpected = $agg['expected'];
            $empDue = $agg['due'];
        }

        return [
            'count' => $sales->count(),
            'sell' => $sell,
            'buy' => $buy,
            'profit' => $profit,
            'commission' => $customerCommission,
            'remaining' => $remaining,
            'employee_commission_expected' => $empExpected,
        ];
    }

    public function resetFilters()
    {
        $this->reset([
            'employeeId',
            'serviceTypeFilter',
            'providerFilter',
            'startDate',
            'endDate',
            'search',
            'viewType',
            'drillType',
            'drillValue',
        ]);
        $this->viewType = 'summary';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // ======= PDF =======
    public function exportToPdf()
    {
        $this->employeeId = request('employeeId', $this->employeeId);
        $this->startDate = request('startDate', $this->startDate);
        $this->endDate = request('endDate', $this->endDate);
        $this->serviceTypeFilter = request('serviceTypeFilter', $this->serviceTypeFilter);
        $this->providerFilter = request('providerFilter', $this->providerFilter);
        $this->search = request('search', $this->search);
        $this->drillType = request('drillType', $this->drillType);
        $this->drillValue = request('drillValue', $this->drillValue);

        $agency = auth()->user()->agency;
        $logoData = null;
        $logoMime = 'image/png';
        if ($agency && $agency->logo) {
            $path = storage_path('app/public/' . $agency->logo);
            if (is_file($path)) {
                $logoData = base64_encode(file_get_contents($path));
                $logoMime = mime_content_type($path) ?: 'image/png';
            }
        }

        $data = $this->prepareReportData(applyDrill: (bool) $this->employeeId);
        $summary = $this->perEmployeeRows();

        $view = $this->employeeId
            ? 'reports.employee-sales-details-pdf'
            : 'reports.employee-sales-summary-pdf';

        $html = view($view, [
            'agency' => $agency,
            'logoData' => $logoData,
            'logoMime' => $logoMime,
            'currency' => $data['agency']->currency ?? 'USD',
            'employee' => $data['employee'],
            'perEmployee' => $summary,
            'byService' => $data['byService'],
            'byMonth' => $data['byMonth'],
            'sales' => $data['sales'],
            'totals' => $data['totals'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
        ])->render();

        return response(
            Browsershot::html($html)
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 10, 10)
                ->emulateMedia('screen')
                ->noSandbox()
                ->waitUntilNetworkIdle()
                ->pdf()
        )->withHeaders([
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="employee-sales-report.pdf"',
                ]);
    }

    // ======= Excel =======
    public function exportToExcel()
    {
        $this->employeeId = request('employeeId', $this->employeeId);
        $this->startDate = request('startDate', $this->startDate);
        $this->endDate = request('endDate', $this->endDate);
        $this->serviceTypeFilter = request('serviceTypeFilter', $this->serviceTypeFilter);
        $this->providerFilter = request('providerFilter', $this->providerFilter);
        $this->search = request('search', $this->search);
        $this->drillType = request('drillType', $this->drillType);
        $this->drillValue = request('drillValue', $this->drillValue);

        $data = $this->prepareReportData(applyDrill: (bool) $this->employeeId);
        $summary = $this->perEmployeeRows();
        $currency = $data['agency']->currency ?? 'USD';

        return Excel::download(
            new \App\Exports\EmployeeSalesReportExport(
                employeeId: $this->employeeId ?: null,
                currency: $currency,
                perEmployee: $summary,
                byService: $data['byService'],
                byMonth: $data['byMonth'],
                sales: $data['sales']
            ),
            'employee-sales-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // إعداد البيانات
    protected function prepareReportData(bool $applyDrill = false)
    {
        $user = Auth::user();
        $agency = $user->agency;
        $employee = $this->employeeId ? User::find($this->employeeId) : null;

        $query = ($applyDrill && $this->employeeId) ? $this->operationsQuery() : $this->baseQuery();

        $sales = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->withSum('collections', 'amount')
            ->get();

        $totals = $this->computeTotals($sales);

        $byService = $sales->groupBy('service_type_id')->map(function ($group) use ($employee) {
            $sell = (float) $group->sum('usd_sell');
            $buy = (float) $group->sum('usd_buy');
            $profit = $sell - $buy;

            $customerCommission = (float) $group->sum(fn($s) => $this->effectiveCustomerCommission($s));

            $agg = $this->aggCommissionLikeIndex($group, $employee);
            $empExpected = $agg['expected'];
            $empDue = $agg['due'];

            $paid = (float) $group->map(function ($s) {
                if (in_array($s->status, ['Refund-Full', 'Refund-Partial']))
                    return 0;
                return (float) ($s->amount_paid ?? 0)
                    + (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
            })->sum();

            return [
                'count' => $group->count(),
                'sell' => $sell,
                'buy' => $buy,
                'profit' => $profit,
                'commission' => $customerCommission,
                'remaining' => $this->debtFromNetForRows($group),
                'firstRow' => $group->first(),
                'employee_commission_expected' => $empExpected,
            ];
        });

        $byMonth = $sales->groupBy(fn($s) => Carbon::parse($s->sale_date)->format('Y-m'))
            ->map(function ($group) use ($employee) {
                $sell = (float) $group->sum('usd_sell');
                $buy = (float) $group->sum('usd_buy');
                $profit = $sell - $buy;

                $customerCommission = (float) $group->sum(fn($s) => $this->effectiveCustomerCommission($s));

                $agg = $this->aggCommissionLikeIndex($group, $employee);
                $empExpected = $agg['expected'];
                $empDue = $agg['due'];

                $paid = (float) $group->map(function ($s) {
                    if (in_array($s->status, ['Refund-Full', 'Refund-Partial']))
                        return 0;
                    return (float) ($s->amount_paid ?? 0)
                        + (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
                })->sum();

                return [
                    'count' => $group->count(),
                    'sell' => $sell,
                    'buy' => $buy,
                    'profit' => $profit,
                    'commission' => $customerCommission,
                    'remaining' => $this->debtFromNetForRows($group),
                    'employee_commission_expected' => $empExpected,
                ];
            })
            ->sortKeysDesc();

        return [
            'agency' => $agency,
            'sales' => $sales,
            'totals' => $totals,
            'byService' => $byService,
            'byMonth' => $byMonth,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'employee' => $this->employeeId ? User::find($this->employeeId) : null,
            'viewType' => $this->viewType,
        ];
    }

    // === مثل Sales\Index لكن مع هدف الأشهر ===
    protected function aggCommissionLikeIndex($rows, ?User $employee = null): array
    {
        $byMonth = $rows->groupBy(fn($s) => \Carbon\Carbon::parse($s->sale_date)->format('Y-m'));

        $expected = 0.0;
        $due = 0.0;
        $totalProfit = 0.0;
        $totalCollectedProfit = 0.0;

        foreach ($byMonth as $ym => $monthRows) {
            [$y, $m] = array_map('intval', explode('-', $ym));
            $rate = $this->monthlyRateFor($employee, $y, $m) / 100.0;
            $target = $this->monthlyTargetFor($employee, $y, $m);

            $groups = $monthRows->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

            $monthProfit = 0.0;
            $monthCollectedProfit = 0.0;

            foreach ($groups as $g) {
                $netSell = (float) $g->sum('usd_sell');
                if ($netSell <= 0)
                    continue;

                $collectionsSum = (float) $g->sum(function ($s) {
                    return (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
                });
                $netCollected = (float) $g->sum('amount_paid') + $collectionsSum;

                $gProfit = (float) $g->sum('sale_profit');

                $hasRefund = $g->contains(function ($row) {
                    $st = mb_strtolower((string) ($row->status ?? ''));
                    return str_contains($st, 'refund') || (float) $row->usd_sell < 0;
                });

                $monthProfit += $hasRefund
                    ? (float) $g->filter(fn($row) => (float) $row->sale_profit > 0)->sum('sale_profit')
                    : $gProfit;

                if ($netCollected + 0.01 >= $netSell) {
                    $monthCollectedProfit += $gProfit;
                }
            }

            $expected += max(($monthProfit - $target) * $rate, 0);
            $due += max(($monthCollectedProfit - $target) * $rate, 0);

            $totalProfit += $monthProfit;
            $totalCollectedProfit += $monthCollectedProfit;
        }

        return [
            'expected' => round($expected, 2),
            'due' => round($due, 2),
            'totalProfit' => round($totalProfit, 2),
            'totalCollectedProfit' => round($totalCollectedProfit, 2),
        ];
    }


    // === توزيع عمولة الصفوف بعد "بوابة الهدف" بحسب الأشهر ===
    protected function commissionPerRowWithTargetGate($rows, ?User $employee): array
    {
        $groups = $rows->groupBy(fn($s) => $s->sale_group_id ?: $s->id)
            ->sortBy(fn($g) => $g->min('sale_date'));

        $perSale = [];
        $ymStats = []; // لكل شهر: cumProfit, cumCollectedProfit, rate, target

        foreach ($groups as $group) {
            $firstDate = \Carbon\Carbon::parse($group->min('sale_date'));
            $y = (int) $firstDate->year;
            $m = (int) $firstDate->month;
            $key = $firstDate->format('Y-m');

            $rate = ($ymStats[$key]['rate'] ??= $this->monthlyRateFor($employee, $y, $m) / 100.0);
            $target = ($ymStats[$key]['target'] ??= $this->monthlyTargetFor($employee, $y, $m));
            $cumP = ($ymStats[$key]['cumProfit'] ?? 0.0);
            $cumC = ($ymStats[$key]['cumCollectedProfit'] ?? 0.0);

            $groupProfit = (float) $group->sum('sale_profit');
            $netSell = (float) $group->sum('usd_sell');

            $collectionsSum = (float) $group->sum(function ($s) {
                return (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
            });
            $netCollected = (float) $group->sum('amount_paid') + $collectionsSum;

            $isCollected = ($netSell > 0) && ($netCollected + 0.01 >= $netSell);

            $prior = $cumP;
            $after = $cumP + $groupProfit;
            $eligibleProfitExp = max(0, $after - $target) - max(0, $prior - $target);
            $cumP = $after;

            $priorDue = $cumC;
            $afterDue = $cumC + ($isCollected ? $groupProfit : 0.0);
            $eligibleProfitDue = max(0, $afterDue - $target) - max(0, $priorDue - $target);
            $cumC = $afterDue;

            $ymStats[$key]['cumProfit'] = $cumP;
            $ymStats[$key]['cumCollectedProfit'] = $cumC;

            $groupExpected = round($eligibleProfitExp * $rate, 2);
            $groupDue = round($eligibleProfitDue * $rate, 2);

            $sumNetProfit = 0.0;
            $sumCollectedProfit = 0.0;
            $rowsPP = [];

            foreach ($group as $sale) {
                $pp = $this->profitParts($sale);
                $rowsPP[$sale->id] = $pp;
                $sumNetProfit += (float) $pp['net_profit'];
                $sumCollectedProfit += (float) $pp['collected_profit'];
            }

            foreach ($group as $sale) {
                $pp = $rowsPP[$sale->id];
                $expShare = ($sumNetProfit > 0) ? ($pp['net_profit'] / $sumNetProfit) : 0.0;
                $dueShare = ($sumCollectedProfit > 0) ? ($pp['collected_profit'] / $sumCollectedProfit) : 0.0;

                $perSale[$sale->id]['exp'] = round($groupExpected * $expShare, 2);
                $perSale[$sale->id]['due'] = round($groupDue * $dueShare, 2);
            }
        }

        return $perSale;
    }

    public function render()
    {
        $sales = $this->operationsQuery()
            ->withSum('collections', 'amount')
            ->addSelect(['amount_paid', 'usd_sell', 'status']) // إضافة الحقول المطلوبة
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(12);

        static $gateMap = null;
        if ($this->employeeId && $gateMap === null) {
            $allRowsForGate = $this->operationsQuery()
                ->withSum('collections', 'amount')
                ->addSelect(['amount_paid', 'usd_sell', 'status']) // هنا أيضًا
                ->orderBy('sale_date', 'asc')
                ->get();

            $gateMap = $this->commissionPerRowWithTargetGate($allRowsForGate, User::find($this->employeeId));
        }

        $sales->each(function ($sale) use ($gateMap) {
            $st = strtolower((string) ($sale->status ?? ''));
            $isRefundOrVoid = in_array($st, [
                'refund-full',
                'refund_full',
                'refund-partial',
                'refund_partial',
                'refunded',
                'refund',
                'void',
                'cancel',
                'canceled',
                'cancelled'
            ], true);

            // حساب المتحصل بشكل صحيح
            $paid = $isRefundOrVoid ? 0
                : (float) ($sale->amount_paid ?? 0) + (float) ($sale->collections_sum_amount ?? 0);

            $sale->remaining_payment = (float) ($sale->usd_sell ?? 0) - $paid;
            $sale->total_paid = $paid; // إضافة للحصول على قيمة المتحصل

            if ($this->employeeId && isset($gateMap[$sale->id])) {
                $sale->employee_commission_expected = $gateMap[$sale->id]['exp'];
                $sale->employee_commission_due = $gateMap[$sale->id]['due'];
            } else {
                $rate = $this->employeeCommissionRate($sale->user) / 100.0;
                $pp = $this->profitParts($sale);
                $sale->employee_commission_expected = round($pp['net_profit'] * $rate, 2);
                $sale->employee_commission_due = round($pp['collected_profit'] * $rate, 2);
            }

            $sale->effective_customer_commission = $this->effectiveCustomerCommission($sale);
        });

        $data = $this->prepareReportData();
        $perEmployee = $this->perEmployeeRows();

        return view('livewire.agency.reportsView.employee-sales-report', [
            'sales' => $sales,
            'totals' => $data['totals'],
            'byService' => $data['byService'],
            'byMonth' => $data['byMonth'],
            'employees' => $this->employees,
            'serviceTypes' => $this->serviceTypes,
            'providers' => $this->providers,
            'perEmployee' => $perEmployee,
        ]);
    }
    // طباعة PDF لعملية واحدة
    public function printPdf($saleId)
    {
        $sale = Sale::with(['customer', 'provider', 'user', 'service'])->findOrFail($saleId);

        $html = view('reports.sale-single', compact('sale'))->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->landscape()
            ->margins(10, 10, 10, 10)
            ->emulateMedia('screen')
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->pdf();

        return response()->streamDownload(
            fn() => print ($pdf),
            'sale-details-' . $sale->id . '.pdf'
        );
    }


    protected function monthlyRateFor(?User $user, int $year, int $month): float
    {
        if (!$user)
            return 20.0;

        $override = EmployeeMonthlyTarget::where('user_id', $user->id)
            ->where('year', $year)->where('month', $month)
            ->value('override_rate');

        if (!is_null($override))
            return (float) $override;

        if (!is_null($user->commission_rate) && $user->commission_rate > 0) {
            return (float) $user->commission_rate;
        }
        if (!is_null($user->commission_percentage) && $user->commission_percentage > 0) {
            return (float) $user->commission_percentage;
        }

        $profileRate = CommissionProfile::where('agency_id', $user->agency_id)
            ->where('is_active', 1)->value('employee_rate');

        return (!is_null($profileRate) && $profileRate > 0) ? (float) $profileRate : 20.0;
    }

    protected function monthlyTargetFor(?User $user, int $year, int $month): float
    {
        if (!$user)
            return 0.0;
        return (float) (EmployeeMonthlyTarget::where('user_id', $user->id)
            ->where('year', $year)->where('month', $month)
            ->value('main_target') ?? 0.0);
    }

    private function netForCustomer(int $customerId): float
    {
        $refund = ['refund-full', 'refund_full', 'refund-partial', 'refund_partial', 'refunded', 'refund'];
        $void = ['void', 'cancel', 'canceled', 'cancelled'];

        $sumD = 0.0; // عليه
        $sumC = 0.0; // له

        // معاملات المحفظة
        $walletTx = WalletTransaction::whereHas('wallet', fn($q) => $q->where('customer_id', $customerId))
            ->orderBy('created_at')->get();

        $walletWithdrawAvail = [];
        foreach ($walletTx as $t) {
            if (strtolower((string) $t->type) === 'withdraw') {
                $k = $this->minuteKey($t->created_at) . '|' . $this->moneyKey($t->amount);
                $walletWithdrawAvail[$k] = ($walletWithdrawAvail[$k] ?? 0) + 1;
            }
        }

        // المبيعات
        $refundCreditKeys = [];
        $sales = Sale::where('customer_id', $customerId)->orderBy('created_at')->get();

        foreach ($sales as $s) {
            $st = mb_strtolower(trim((string) $s->status));

            if (!in_array($st, $refund, true) && !in_array($st, $void, true)) {
                $sumD += (float) ($s->invoice_total_true ?? $s->usd_sell ?? 0);
            }

            if (in_array($st, $refund, true) || in_array($st, $void, true)) {
                $amt = (float) ($s->refund_amount ?? 0);
                if ($amt <= 0)
                    $amt = abs((float) ($s->usd_sell ?? 0));
                $sumC += $amt;

                $keyR = $this->minuteKey($s->created_at) . '|' . $this->moneyKey($amt);
                $refundCreditKeys[$keyR] = ($refundCreditKeys[$keyR] ?? 0) + 1;
            }

            if ((float) $s->amount_paid > 0) {
                $sumC += (float) $s->amount_paid;
            }
        }

        // التحصيلات
        $collections = Collection::with('sale')
            ->whereHas('sale', fn($q) => $q->where('customer_id', $customerId))
            ->orderBy('created_at')->get();

        $collectionKeys = [];
        foreach ($collections as $c) {
            $evt = $this->minuteKey($c->created_at ?? $c->payment_date);
            $k = $evt . '|' . $this->moneyKey($c->amount);
            $collectionKeys[$k] = ($collectionKeys[$k] ?? 0) + 1;
        }

        foreach ($collections as $c) {
            $evt = $this->minuteKey($c->created_at ?? $c->payment_date);
            $k = $evt . '|' . $this->moneyKey($c->amount);

            if (($refundCreditKeys[$k] ?? 0) > 0) {
                $refundCreditKeys[$k]--;
                continue;
            }
            if (($walletWithdrawAvail[$k] ?? 0) > 0) {
                $walletWithdrawAvail[$k]--;
                continue;
            }

            $sumC += (float) $c->amount;
        }

        // المحفظة (تجاهل sales-auto|group للايداع الآلي تبع الاسترداد)
        foreach ($walletTx as $tx) {
            $evt = $this->minuteKey($tx->created_at);
            $k = $evt . '|' . $this->moneyKey($tx->amount);
            $type = strtolower((string) $tx->type);
            $ref = Str::lower((string) $tx->reference);

            if ($type === 'deposit') {
                if (Str::contains($ref, 'sales-auto|group:'))
                    continue;
                if (($refundCreditKeys[$k] ?? 0) > 0) {
                    $refundCreditKeys[$k]--;
                    continue;
                }
                $sumC += (float) $tx->amount;
            } elseif ($type === 'withdraw') {
                if (($collectionKeys[$k] ?? 0) > 0) {
                    $collectionKeys[$k]--;
                    continue;
                }
                $sumD += (float) $tx->amount;
            }
        }

        return round($sumC - $sumD, 2);
    }

    private function minuteKey($dt): string
    {
        try {
            return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) $dt;
        }
    }
    private function moneyKey($n): string
    {
        return number_format((float) $n, 2, '.', '');
    }

}
