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
        'commission' => 0, // عمولة العميل (قديمة)
        'remaining' => 0,
        // الحقول الجديدة
        'employee_commission_expected' => 0,
        'employee_commission_due'      => 0,
    ];

    // ✅ التفصيلي (drill-down) يؤثر على جدول العمليات + التصدير عند اختيار موظف
    public ?string $drillType = null;   // 'service' | 'month' | null
    public ?string $drillValue = null;  // service_type_id أو 'YYYY-MM'

    protected $queryString = [
        'employeeId'        => ['except' => ''],
        'serviceTypeFilter' => ['except' => ''],
        'providerFilter'    => ['except' => ''],
        'startDate'         => ['except' => ''],
        'endDate'           => ['except' => ''],
        'search'            => ['except' => ''],
        'viewType'          => ['except' => 'summary'],
        'sortField'         => ['except' => 'sale_date'],
        'sortDirection'     => ['except' => 'desc'],
        'drillType'         => ['except' => null],
        'drillValue'        => ['except' => null],
    ];

    public function mount()
    {
        $agencyId = Auth::user()->agency_id;

        // قراءة employeeId من الـ URL وتفعيل التفاصيل
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

    // أزرار التحكم
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

    // ضبط/إلغاء الـdrill
    public function setDrill(string $type, string $value): void
    {
        $this->drillType  = $type;   // 'service' أو 'month'
        $this->drillValue = $value;  // id أو 'YYYY-MM'
        $this->resetPage();
    }

    public function clearDrill(): void
    {
        $this->drillType = null;
        $this->drillValue = null;
        $this->resetPage();
    }

    // =========================
    // 🔢 جلب نسبة عمولة الموظف
    protected function employeeCommissionRate(?User $user): float
    {
        if (!$user) return 0.0;

        // حاول حقول شائعة أولاً
        $rate = null;
        if (isset($user->commission_rate))       $rate = $user->commission_rate;
        if ($rate === null && isset($user->commission_percentage)) $rate = $user->commission_percentage;

        // إن لم توجد بالموظف استخدم إعداد الوكالة إن وُجد
        if ($rate === null) {
            $agency = $user->agency;
            if ($agency && isset($agency->employee_commission_rate)) {
                $rate = $agency->employee_commission_rate;
            }
        }

        return (float) max(0, $rate ?? 0);
    }

    // ✅ عمولة العملية بعد مراعاة الاسترداد (مطابقة لمنطق التهيئة إن رغبت)
    protected function effectiveCustomerCommission($sale): float
    {
        $base = (float) ($sale->commission ?? 0);
        if ($sale->status === 'Refund-Full') {
            return 0.0;
        }

        $refundedCommission = (float) ($sale->refunded_commission ?? 0);
        if ($refundedCommission > 0) {
            return max(0.0, $base - $refundedCommission);
        }

        $refundedAmount = (float) ($sale->refunded_amount ?? 0);
        $sell           = (float) ($sale->usd_sell ?? 0);

        if ($sale->status === 'Refund-Partial' && $sell > 0 && $refundedAmount > 0) {
            $ratio = max(0.0, min(1.0, ($sell - $refundedAmount) / $sell));
            return round($base * $ratio, 2);
        }

        return $base;
    }

    // 🧮 أجزاء الربح بعد الاسترداد والتحصيل
    protected function profitParts($sale): array
    {
        $sell  = (float) ($sale->usd_sell ?? 0);
        $buy   = (float) ($sale->usd_buy  ?? 0);
        $baseProfit = $sell - $buy;

        // صافي البيع بعد الاسترداد
        if ($sale->status === 'Refund-Full') {
            $netSell = 0.0;
        } else {
            $refundedAmount = (float) ($sale->refunded_amount ?? 0);
            $netSell = max(0.0, $sell - $refundedAmount);
        }

        // الربح الصافي بعد الاسترداد (بنسبة صافي البيع)
        $netProfit = ($sell > 0)
            ? round($baseProfit * ($netSell / $sell), 2)
            : 0.0;

        // المحصل (مبالغ قبض)
        $collected = (float) ($sale->amount_paid ?? 0);
        if (isset($sale->collections_sum_amount)) {
            $collected += (float) $sale->collections_sum_amount;
        } else {
            $collected += (float) $sale->collections->sum('amount');
        }

        // لا نتجاوز صافي البيع
        $collected = min($collected, $netSell);

        // نسبة التحصيل من صافي البيع
        $collectRatio = ($netSell > 0) ? min(1.0, $collected / $netSell) : 0.0;

        // الربح المُحصّل
        $collectedProfit = round($netProfit * $collectRatio, 2);

        return [
            'sell'            => $sell,
            'buy'             => $buy,
            'base_profit'     => $baseProfit,
            'net_sell'        => $netSell,
            'net_profit'      => $netProfit,
            'collected'       => $collected,
            'collected_profit'=> $collectedProfit,
        ];
    }
    // =========================

    // الاستعلام الأساسي (للملخصات والتجميعات)
    protected function baseQuery()
    {
        $user = Auth::user();
        $agency = $user->agency;

        // الوكالة + الفروع
        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        return Sale::with(['user','service','provider','customer','collections'])
            ->whereIn('agency_id', $agencyIds)
            ->when($this->employeeId, fn($q) => $q->where('user_id', $this->employeeId))
            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when($this->startDate && $this->endDate, fn($q) =>
                $q->whereBetween('sale_date', [$this->startDate, $this->endDate])
            )
            ->when($this->startDate && !$this->endDate, fn($q) =>
                $q->whereDate('sale_date', '>=', $this->startDate)
            )
            ->when(!$this->startDate && $this->endDate, fn($q) =>
                $q->whereDate('sale_date', '<=', $this->endDate)
            )
            ->when($this->search, function ($q) {
                $term = "%{$this->search}%";
                $q->where(function ($qq) use ($term) {
                    $qq->where('beneficiary_name','like',$term)
                       ->orWhere('reference','like',$term)
                       ->orWhere('pnr','like',$term);
                });
            });
    }

    // استعلام جدول العمليات (يُطبق عليه الـdrill)
    protected function operationsQuery()
    {
        return $this->baseQuery()
            ->when($this->drillType === 'service' && $this->drillValue, fn($q) =>
                $q->where('service_type_id', $this->drillValue)
            )
            ->when($this->drillType === 'month' && $this->drillValue, function ($q) {
                $start = Carbon::createFromFormat('Y-m', $this->drillValue)->startOfMonth()->toDateString();
                $end   = Carbon::createFromFormat('Y-m', $this->drillValue)->endOfMonth()->toDateString();
                $q->whereBetween('sale_date', [$start, $end]);
            });
    }

    // ملخص لكل موظف
   protected function perEmployeeRows()
{
    $sales = $this->baseQuery()->with(['collections'])->withSum('collections','amount')->get();

    $grouped = $sales->groupBy('user_id')->map(function ($rows) {
        $sell = (float) $rows->sum('usd_sell');
        $buy  = (float) $rows->sum('usd_buy');
        $profit = $sell - $buy;

        // عمولة العميل (قديمة) مع مراعاة الاسترداد
        $customerCommission = (float) $rows->sum(fn($s) => $this->effectiveCustomerCommission($s));

        $user = $rows->first()?->user;

        // نفس منطق Sales\Index:
        $agg = $this->aggCommissionLikeIndex($rows, $user);

        // المتبقي كما كان
        $paid = (float) $rows->map(function ($s) {
            if (in_array($s->status, ['Refund-Full','Refund-Partial'])) return 0;
            return (float) ($s->amount_paid ?? 0) + (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
        })->sum();

        return [
            'user'       => $user,
            'count'      => $rows->count(),
            'sell'       => $sell,
            'buy'        => $buy,
            'profit'     => $profit,
            'commission' => $customerCommission,
            'remaining'  => $sell - $paid,

            // مطابقة تامة لمنطق الصفحة
            'employee_commission_expected' => $agg['expected'],
            'employee_commission_due'      => $agg['due'],
        ];
    });

    return $grouped->sortBy(fn($r) => $r['user']?->name ?? '');
}


   protected function computeTotals($sales)
{
    $sell = (float) $sales->sum('usd_sell');
    $buy  = (float) $sales->sum('usd_buy');
    $profit = $sell - $buy;

    $customerCommission = (float) $sales->sum(fn($s) => $this->effectiveCustomerCommission($s));

    // احتساب المتبقي كما كان
    $totalPaid = $sales->map(function ($sale) {
        if (in_array($sale->status, ['Refund-Full','Refund-Partial'])) {
            return 0;
        }
        return (float) ($sale->amount_paid ?? 0)
             + (float) ($sale->collections_sum_amount ?? $sale->collections->sum('amount'));
    })->sum();
    $remaining = $sell - $totalPaid;

    // لو في موظف محدد: طبّق منطق Sales\Index حرفيًا (تجميع على مستوى المجموعات + هدف الموظف + 17%)
    $empExpected = 0.0;
    $empDue      = 0.0;

    if ($this->employeeId) {
        $user = User::find($this->employeeId);
        $agg  = $this->aggCommissionLikeIndex($sales, $user);
        $empExpected = $agg['expected'];
        $empDue      = $agg['due'];
    }

    return [
        'count'      => $sales->count(),
        'sell'       => $sell,
        'buy'        => $buy,
        'profit'     => $profit,
        'commission' => $customerCommission,
        'remaining'  => $remaining,
        'employee_commission_expected' => $empExpected,
        'employee_commission_due'      => $empDue,
    ];
}

    public function resetFilters()
    {
        $this->reset([
            'employeeId','serviceTypeFilter','providerFilter',
            'startDate','endDate','search','viewType',
            'drillType','drillValue',
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

    // ======= تصدير PDF مع احترام الفلاتر + الـdrill =======
    public function exportToPdf()
    {
        // قراءة الفلاتر من الرابط
        $this->employeeId        = request('employeeId', $this->employeeId);
        $this->startDate         = request('startDate', $this->startDate);
        $this->endDate           = request('endDate', $this->endDate);
        $this->serviceTypeFilter = request('serviceTypeFilter', $this->serviceTypeFilter);
        $this->providerFilter    = request('providerFilter', $this->providerFilter);
        $this->search            = request('search', $this->search);
        $this->drillType         = request('drillType', $this->drillType);
        $this->drillValue        = request('drillValue', $this->drillValue);
    
        // تجهيز الشعار (base64)
        $agency   = auth()->user()->agency;
        $logoData = null; $logoMime = 'image/png';
        if ($agency && $agency->logo) {
            $path = storage_path('app/public/'.$agency->logo);
            if (is_file($path)) {
                $logoData = base64_encode(file_get_contents($path));
                $logoMime = mime_content_type($path) ?: 'image/png';
            }
        }
    
        // لو في موظف محدد نطبق الـdrill على التصدير
        $data    = $this->prepareReportData(applyDrill: (bool)$this->employeeId);
        $summary = $this->perEmployeeRows();
    
        $view = $this->employeeId
            ? 'reports.employee-sales-details-pdf'
            : 'reports.employee-sales-summary-pdf';
    
        $html = view($view, [
            'agency'      => $agency,
            'logoData'    => $logoData,
            'logoMime'    => $logoMime,
            'currency'    => $data['agency']->currency ?? 'USD',
            'employee'    => $data['employee'],
            'perEmployee' => $summary,
            'byService'   => $data['byService'],
            'byMonth'     => $data['byMonth'],
            'sales'       => $data['sales'],
            'totals'      => $data['totals'],
            'startDate'   => $data['startDate'],
            'endDate'     => $data['endDate'],
        ])->render();
    
        return response(
            Browsershot::html($html)
                ->format('A4')
                ->landscape() // ✅ أفقي
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
    

    // ======= تصدير Excel مع احترام الفلاتر + الـdrill =======
    public function exportToExcel()
    {
        $this->employeeId        = request('employeeId', $this->employeeId);
        $this->startDate         = request('startDate', $this->startDate);
        $this->endDate           = request('endDate', $this->endDate);
        $this->serviceTypeFilter = request('serviceTypeFilter', $this->serviceTypeFilter);
        $this->providerFilter    = request('providerFilter', $this->providerFilter);
        $this->search            = request('search', $this->search);
        $this->drillType         = request('drillType', $this->drillType);
        $this->drillValue        = request('drillValue', $this->drillValue);

        $data     = $this->prepareReportData(applyDrill: (bool)$this->employeeId);
        $summary  = $this->perEmployeeRows();
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
            'employee-sales-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    // إعداد البيانات للتقارير والجداول
protected function prepareReportData(bool $applyDrill = false)
{
    $user     = Auth::user();
    $agency   = $user->agency;
    $employee = $this->employeeId ? User::find($this->employeeId) : null;

    // لو نُريد التصدير وفق التفصيلي نستخدم operationsQuery()
    $query = ($applyDrill && $this->employeeId) ? $this->operationsQuery() : $this->baseQuery();

    $sales = $query
        ->orderBy($this->sortField, $this->sortDirection)
        ->withSum('collections','amount')
        ->get();

    $totals = $this->computeTotals($sales);

    // تفصيلي حسب الخدمة
    $byService = $sales->groupBy('service_type_id')->map(function ($group) use ($employee) {
        $sell   = (float) $group->sum('usd_sell');
        $buy    = (float) $group->sum('usd_buy');
        $profit = $sell - $buy;

        // عمولة العميل (قديمة) مع مراعاة الاسترداد
        $customerCommission = (float) $group->sum(function ($s) {
            return $this->effectiveCustomerCommission($s);
        });

        // نفس منطق صفحة المبيعات (تجميع على مستوى المجموعات + طرح الهدف + 17%)
        $agg         = $this->aggCommissionLikeIndex($group, $employee);
        $empExpected = $agg['expected'];
        $empDue      = $agg['due'];

        // المدفوع لاستخدامه في المتبقي كما كان
        $paid = (float) $group->map(function ($s) {
            if (in_array($s->status, ['Refund-Full','Refund-Partial'])) return 0;
            return (float) ($s->amount_paid ?? 0)
                 + (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
        })->sum();

        return [
            'count'      => $group->count(),
            'sell'       => $sell,
            'buy'        => $buy,
            'profit'     => $profit,
            'commission' => $customerCommission,
            'remaining'  => $sell - $paid,
            'firstRow'   => $group->first(),

            // الجديدة
            'employee_commission_expected' => $empExpected,
            'employee_commission_due'      => $empDue,
        ];
    });

    // تفصيلي حسب الشهر
    $byMonth = $sales->groupBy(fn($s) => Carbon::parse($s->sale_date)->format('Y-m'))
        ->map(function ($group) use ($employee) {
            $sell   = (float) $group->sum('usd_sell');
            $buy    = (float) $group->sum('usd_buy');
            $profit = $sell - $buy;

            $customerCommission = (float) $group->sum(function ($s) {
                return $this->effectiveCustomerCommission($s);
            });

            // نفس منطق صفحة المبيعات
            $agg         = $this->aggCommissionLikeIndex($group, $employee);
            $empExpected = $agg['expected'];
            $empDue      = $agg['due'];

            $paid = (float) $group->map(function ($s) {
                if (in_array($s->status, ['Refund-Full','Refund-Partial'])) return 0;
                return (float) ($s->amount_paid ?? 0)
                     + (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
            })->sum();

            return [
                'count'      => $group->count(),
                'sell'       => $sell,
                'buy'        => $buy,
                'profit'     => $profit,
                'commission' => $customerCommission,
                'remaining'  => $sell - $paid,

                // الجديدة
                'employee_commission_expected' => $empExpected,
                'employee_commission_due'      => $empDue,
            ];
        })
        ->sortKeysDesc();

    return [
        'agency'    => $agency,
        'sales'     => $sales,
        'totals'    => $totals,
        'byService' => $byService,
        'byMonth'   => $byMonth,
        'startDate' => $this->startDate,
        'endDate'   => $this->endDate,
        'employee'  => $this->employeeId ? User::find($this->employeeId) : null,
        'viewType'  => $this->viewType,
    ];
}

    
    public function render()
    {
        // جدول العمليات (يُطبق عليه الـdrill)
        $sales = $this->operationsQuery()
            ->withSum('collections','amount')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(12);

        $sales->each(function ($sale) {
            $paid = in_array($sale->status, ['Refund-Full','Refund-Partial'])
                ? 0
                : (float)($sale->amount_paid ?? 0) + (float)$sale->collections_sum_amount;

            $sale->remaining_payment = (float)($sale->usd_sell ?? 0) - $paid;

            // 🔸 عمولة الموظف للعرض في الجدول
     // عند عرض تفصيلي لموظف محدد استخدم نفس منطق الصفحة: 17%
        $rate = $this->employeeId
            ? 0.17
            : ($this->employeeCommissionRate($sale->user) / 100.0);

        $pp   = $this->profitParts($sale);
        $sale->employee_commission_expected = round($pp['net_profit'] * $rate, 2);
        $sale->employee_commission_due      = round($pp['collected_profit'] * $rate, 2);


            // لو تحتاج أيضًا عمولة العميل بعد الاسترداد:
            $sale->effective_customer_commission = $this->effectiveCustomerCommission($sale);
        });

        $data = $this->prepareReportData();   // الملخصات والجداول التجميعية
        $perEmployee = $this->perEmployeeRows();

        return view('livewire.agency.reportsView.employee-sales-report', [
            'sales'        => $sales,
            'totals'       => $data['totals'],
            'byService'    => $data['byService'],
            'byMonth'      => $data['byMonth'],
            'employees'    => $this->employees,
            'serviceTypes' => $this->serviceTypes,
            'providers'    => $this->providers,
            'perEmployee'  => $perEmployee,
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
            fn () => print($pdf),
            'sale-details-' . $sale->id . '.pdf'
        );
    }


    // === مثل Sales\Index بالضبط: تجميع الربح والتحصيل على مستوى المجموعات ثم حساب العمولات ===
protected function aggCommissionLikeIndex($rows, ?User $employee = null): array
{
    $groups = $rows->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

    $totalProfit = 0.0;
    $totalCollectedProfit = 0.0;

    foreach ($groups as $group) {
        $netSell = (float) $group->sum('usd_sell');
        if ($netSell <= 0) {
            continue;
        }

        // amount_paid + collections (يدعم withSum أو العلاقة)
        $collectionsSum = (float) $group->sum(function ($s) {
            return (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
        });
        $netCollected = (float) $group->sum('amount_paid') + $collectionsSum;

        $groupProfit = (float) $group->sum('sale_profit');

        $totalProfit += $groupProfit;

        // سماحية سنت واحد
        if ($netCollected + 0.01 >= $netSell) {
            $totalCollectedProfit += $groupProfit;
        }
    }

    // نفس الصفحة: طرح هدف الموظف ثم 17%
    $target = (float) ($employee?->main_target ?? 0);

    // لجعلها مطابقة تمامًا لصفحة المبيعات نخليها ثابتة 17%
    $rate = 0.17;

    // لو حبيت ترجع للنسبة من بيانات الموظف بدلاً من الثابت:
    // $rate = ($this->employeeCommissionRate($employee) / 100.0);

    $expected = max(($totalProfit - $target) * $rate, 0);
    $due      = max(($totalCollectedProfit - $target) * $rate, 0);

    return [
        'expected' => round($expected, 2),
        'due'      => round($due, 2),
        'totalProfit' => round($totalProfit, 2),
        'totalCollectedProfit' => round($totalCollectedProfit, 2),
    ];
}

}
