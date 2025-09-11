<?php

namespace App\Livewire\Agency\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Sale;
use App\Models\User;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\DynamicListItem;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Models\Collection;
use App\Models\WalletTransaction;
use Illuminate\Support\Str;

#[Layout('layouts.agency')]
class CustomerSalesReport extends Component
{
    use WithPagination;

    // فلاتر عامة
    public $customerId = '';
    public $serviceTypeFilter = '';
    public $providerFilter = '';
    public $startDate = '';
    public $endDate = '';
    public $search = '';

    // ترتيب جدول العمليات
    public $sortField = 'sale_date';
    public $sortDirection = 'desc';

    // قوائم
    public $customers = [];
    public $serviceTypes = [];
    public $providers = [];

    // Drill-down
    public ?string $drillType = null;   // 'service' | 'month' | null
    public ?string $drillValue = null;  // service_type_id أو 'YYYY-MM'

    protected $queryString = [
        'customerId' => ['except' => ''],
        'serviceTypeFilter' => ['except' => ''],
        'providerFilter' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'search' => ['except' => ''],
        'sortField' => ['except' => 'sale_date'],
        'sortDirection' => ['except' => 'desc'],
        'drillType' => ['except' => null],
        'drillValue' => ['except' => null],
    ];

    public function mount()
    {
        $agencyId = Auth::user()->agency_id;

        $this->customers = Customer::where('agency_id', $agencyId)->orderBy('name')->get();
        $this->providers = Provider::where('agency_id', $agencyId)->orderBy('name')->get();

        $this->serviceTypes = DynamicListItem::whereHas('list', function ($q) {
            $q->where('name', 'قائمة الخدمات')
                ->where(function ($qq) {
                    $qq->where('created_by_agency', auth()->user()->agency_id)
                        ->orWhereNull('created_by_agency');
                });
        })->orderBy('order')->get();
    }

    // ضبط/إلغاء الـdrill
    public function setDrill(string $type, string $value): void
    {
        $this->drillType = $type;   // 'service' أو 'month'
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
    // عمولة العميل الفعلية بعد الاسترداد (إن احتجت عرضها)
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
        $sell = (float) ($sale->usd_sell ?? 0);

        if ($sale->status === 'Refund-Partial' && $sell > 0 && $refundedAmount > 0) {
            $ratio = max(0.0, min(1.0, ($sell - $refundedAmount) / $sell));
            return round($base * $ratio, 2);
        }

        return $base;
    }

    protected function baseQuery()
    {
        $user = Auth::user();
        $agency = $user->agency;

        // الوكالة + الفروع
        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        return Sale::with(['user', 'service', 'provider', 'customer', 'collections'])
            ->whereIn('agency_id', $agencyIds)
            ->when($this->customerId, fn($q) => $q->where('customer_id', $this->customerId))
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
            ->with(['user', 'service', 'provider', 'customer', 'collections']) // إضافة العلاقات المطلوبة
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
    protected function perCustomerRows()
    {
        $sales = $this->baseQuery()->with(['collections'])->withSum('collections', 'amount')->get();

        $grouped = $sales->groupBy('customer_id')->map(function ($rows) {
            $sell = (float) $rows->sum('usd_sell');
            $buy = (float) $rows->sum('usd_buy');
            $profit = $sell - $buy;

            $customer = $rows->first()?->customer;
            $net = $this->netForCustomer((int) ($customer?->id)); // صافي (له − عليه)

            return [
                'customer' => $customer,
                'count' => $rows->count(),
                'sell' => $sell,
                'buy' => $buy,
                'profit' => $profit,
                'remaining' => $net < 0 ? abs($net) : 0.0, // دين فقط
            ];
        });

        return $grouped->sortBy(fn($r) => $r['customer']?->name ?? '');
    }
    protected function computeTotals($sales)
    {
        $sell = (float) $sales->sum('usd_sell');
        $buy = (float) $sales->sum('usd_buy');
        $profit = $sell - $buy;

        // اجمع ديون العملاء الظاهرين بصافي العميل الحقيقي
        $remaining = 0.0;
        $customerIds = $sales->pluck('customer_id')->unique()->filter();
        foreach ($customerIds as $cid) {
            $net = $this->netForCustomer((int) $cid);
            if ($net < 0)
                $remaining += abs($net);
        }

        return [
            'count' => $sales->count(),
            'sell' => $sell,
            'buy' => $buy,
            'profit' => $profit,
            'remaining' => round($remaining, 2),
        ];
    }
    public function resetFilters()
    {
        $this->reset([
            'customerId',
            'serviceTypeFilter',
            'providerFilter',
            'startDate',
            'endDate',
            'search',
            'drillType',
            'drillValue',
        ]);
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
    public function exportToPdf()
    {

        $this->customerId = request('customerId', $this->customerId);
        $this->startDate = request('startDate', $this->startDate);
        $this->endDate = request('endDate', $this->endDate);
        $this->serviceTypeFilter = request('serviceTypeFilter', $this->serviceTypeFilter);
        $this->providerFilter = request('providerFilter', $this->providerFilter);
        $this->search = request('search', $this->search);
        $this->drillType = request('drillType', $this->drillType);
        $this->drillValue = request('drillValue', $this->drillValue);

        // شعار الوكالة
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

        $data = $this->prepareReportData(applyDrill: (bool) $this->customerId);
        $summary = $this->perCustomerRows();

        $view = $this->customerId
            ? 'reports.customer-sales-details-pdf'
            : 'reports.customer-sales-summary-pdf';

        $html = view($view, [
            'agency' => $agency,
            'logoData' => $logoData,
            'logoMime' => $logoMime,
            'currency' => $data['agency']->currency ?? 'USD',
            'customer' => $data['customer'],
            'perCustomer' => $summary,
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
                    'Content-Disposition' => 'inline; filename="customer-sales-report.pdf"',
                ]);
    }
    public function exportToExcel()
    {
        $this->customerId = request('customerId', $this->customerId);
        $this->startDate = request('startDate', $this->startDate);
        $this->endDate = request('endDate', $this->endDate);
        $this->serviceTypeFilter = request('serviceTypeFilter', $this->serviceTypeFilter);
        $this->providerFilter = request('providerFilter', $this->providerFilter);
        $this->search = request('search', $this->search);
        $this->drillType = request('drillType', $this->drillType);
        $this->drillValue = request('drillValue', $this->drillValue);

        $data = $this->prepareReportData(applyDrill: (bool) $this->customerId);
        $summary = $this->perCustomerRows();
        $currency = $data['agency']->currency ?? 'USD';

        return Excel::download(
            new \App\Exports\CustomerSalesReportExport(
                customerId: $this->customerId ?: null,
                currency: $currency,
                perCustomer: $summary,
                byService: $data['byService'],
                byMonth: $data['byMonth'],
                sales: $data['sales']
            ),
            'customer-sales-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
    protected function prepareReportData(bool $applyDrill = false)
    {
        $user = Auth::user();
        $agency = $user->agency;
        $customer = $this->customerId ? Customer::find($this->customerId) : null;

        $query = ($applyDrill && $this->customerId) ? $this->operationsQuery() : $this->baseQuery();

        $sales = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->withSum('collections', 'amount')
            ->get();

        $totals = $this->computeTotals($sales);

        $byService = $sales->groupBy('service_type_id')->map(function ($group) {
            $sell = (float) $group->sum('usd_sell');
            $buy = (float) $group->sum('usd_buy');
            $profit = $sell - $buy;

            $remaining = $this->remainingFromRows($group); // ✅ نفس منطق المبيعات

            return [
                'count' => $group->count(),
                'sell' => $sell,
                'buy' => $buy,
                'profit' => $profit,
                'remaining' => $remaining,              // ✅ صحيح الآن
                'firstRow' => $group->first(),
            ];
        });


        $byMonth = $sales->groupBy(fn($s) => Carbon::parse($s->sale_date)->format('Y-m'))
            ->map(function ($group) {
                $sell = (float) $group->sum('usd_sell');
                $buy = (float) $group->sum('usd_buy');
                $profit = $sell - $buy;

                $remaining = $this->remainingFromRows($group); // ✅

                return [
                    'count' => $group->count(),
                    'sell' => $sell,
                    'buy' => $buy,
                    'profit' => $profit,
                    'remaining' => $remaining,           // ✅
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
            'customer' => $customer,
        ];
    }
    public function render()
    {
        $sales = $this->operationsQuery()
            ->withSum('collections', 'amount')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(12);

        $sales->each(function ($sale) {
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

            // إصلاح: إضافة amount_paid من نموذج Sale
            $sell = (float) ($sale->usd_sell ?? 0);
            $paidFromSale = (float) ($sale->amount_paid ?? 0); // هذا هو المفتاح المفقود
            $paidFromCollections = (float) ($sale->collections_sum_amount ?? 0);

            $totalPaid = $isRefundOrVoid ? 0.0 : ($paidFromSale + $paidFromCollections);

            $sale->remaining_payment = ($isRefundOrVoid || $sell <= 0) ? 0.0 : max($sell - $totalPaid, 0);
            $sale->total_paid = $totalPaid; // إضافة هذا الحقل للعرض
            $sale->effective_customer_commission = $this->effectiveCustomerCommission($sale);
        });

        $data = $this->prepareReportData();
        $perCustomer = $this->perCustomerRows();

        return view('livewire.agency.reportsView.customer-sales-report', [
            'sales' => $sales,
            'totals' => $data['totals'],
            'byService' => $data['byService'],
            'byMonth' => $data['byMonth'],
            'customers' => $this->customers,
            'serviceTypes' => $this->serviceTypes,
            'providers' => $this->providers,
            'perCustomer' => $perCustomer,
        ]);
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

        // مفاتيح سحب المحفظة لمعادلة التحصيلات
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
        $collections = \App\Models\Collection::with('sale')
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

        // المحفظة (تجاهل إيداعات استرداد sales-auto|group)
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

        return round($sumC - $sumD, 2); // صافي العميل
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
    private function remainingFromRows($rows): float
    {
        $sum = 0.0;

        foreach ($rows as $s) {
            $st = strtolower(trim((string) ($s->status ?? '')));

            // تجاهل الاسترداد والإلغاء
            if (
                in_array($st, [
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
                ], true)
            ) {
                continue;
            }

            $sell = (float) ($s->usd_sell ?? 0);
            if (round($sell, 2) <= 0.00) {
                continue; // لا دين بدون بيع
            }

            // مبالغ السداد المرتبطة بالعملية
            $paid = (float) ($s->amount_paid ?? 0) + (float) ($s->collections_sum_amount ?? 0);

            $sum += max($sell - $paid, 0);
        }

        return round($sum, 2);
    }
}
