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
        'customerId'        => ['except' => ''],
        'serviceTypeFilter' => ['except' => ''],
        'providerFilter'    => ['except' => ''],
        'startDate'         => ['except' => ''],
        'endDate'           => ['except' => ''],
        'search'            => ['except' => ''],
        'sortField'         => ['except' => 'sale_date'],
        'sortDirection'     => ['except' => 'desc'],
        'drillType'         => ['except' => null],
        'drillValue'        => ['except' => null],
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
        $sell           = (float) ($sale->usd_sell ?? 0);

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

        return Sale::with(['user','service','provider','customer','collections'])
            ->whereIn('agency_id', $agencyIds)
            ->when($this->customerId, fn($q) => $q->where('customer_id', $this->customerId))
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

    protected function perCustomerRows()
    {
        $sales = $this->baseQuery()->with(['collections'])->withSum('collections','amount')->get();

        $grouped = $sales->groupBy('customer_id')->map(function ($rows) {
            $sell = (float) $rows->sum('usd_sell');
            $buy  = (float) $rows->sum('usd_buy');
            $profit = $sell - $buy;

            $paid = (float) $rows->map(function ($s) {
                if (in_array($s->status, ['Refund-Full','Refund-Partial'])) return 0;
                return (float) ($s->amount_paid ?? 0) + (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
            })->sum();

            return [
                'customer'  => $rows->first()?->customer,
                'count'     => $rows->count(),
                'sell'      => $sell,
                'buy'       => $buy,
                'profit'    => $profit,
                'remaining' => $sell - $paid,
            ];
        });

        return $grouped->sortBy(fn($r) => $r['customer']?->name ?? '');
    }

    protected function computeTotals($sales)
    {
        $sell = (float) $sales->sum('usd_sell');
        $buy  = (float) $sales->sum('usd_buy');
        $profit = $sell - $buy;

        $totalPaid = $sales->map(function ($sale) {
            if (in_array($sale->status, ['Refund-Full','Refund-Partial'])) {
                return 0;
            }
            return (float) ($sale->amount_paid ?? 0)
                 + (float) ($sale->collections_sum_amount ?? $sale->collections->sum('amount'));
        })->sum();
        $remaining = $sell - $totalPaid;

        return [
            'count'      => $sales->count(),
            'sell'       => $sell,
            'buy'        => $buy,
            'profit'     => $profit,
            'remaining'  => $remaining,
        ];
    }

    public function resetFilters()
    {
        $this->reset([
            'customerId','serviceTypeFilter','providerFilter',
            'startDate','endDate','search','drillType','drillValue',
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

    // ======= تصدير PDF =======
    public function exportToPdf()
    {
        // قراءة الفلاتر من الرابط
        $this->customerId        = request('customerId', $this->customerId);
        $this->startDate         = request('startDate', $this->startDate);
        $this->endDate           = request('endDate', $this->endDate);
        $this->serviceTypeFilter = request('serviceTypeFilter', $this->serviceTypeFilter);
        $this->providerFilter    = request('providerFilter', $this->providerFilter);
        $this->search            = request('search', $this->search);
        $this->drillType         = request('drillType', $this->drillType);
        $this->drillValue        = request('drillValue', $this->drillValue);

        // شعار الوكالة
        $agency   = auth()->user()->agency;
        $logoData = null; $logoMime = 'image/png';
        if ($agency && $agency->logo) {
            $path = storage_path('app/public/'.$agency->logo);
            if (is_file($path)) {
                $logoData = base64_encode(file_get_contents($path));
                $logoMime = mime_content_type($path) ?: 'image/png';
            }
        }

        $data    = $this->prepareReportData(applyDrill: (bool)$this->customerId);
        $summary = $this->perCustomerRows();

        $view = $this->customerId
            ? 'reports.customer-sales-details-pdf'
            : 'reports.customer-sales-summary-pdf';

        $html = view($view, [
            'agency'      => $agency,
            'logoData'    => $logoData,
            'logoMime'    => $logoMime,
            'currency'    => $data['agency']->currency ?? 'USD',
            'customer'    => $data['customer'],
            'perCustomer' => $summary,
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

    // ======= تصدير Excel =======
    public function exportToExcel()
    {
        $this->customerId        = request('customerId', $this->customerId);
        $this->startDate         = request('startDate', $this->startDate);
        $this->endDate           = request('endDate', $this->endDate);
        $this->serviceTypeFilter = request('serviceTypeFilter', $this->serviceTypeFilter);
        $this->providerFilter    = request('providerFilter', $this->providerFilter);
        $this->search            = request('search', $this->search);
        $this->drillType         = request('drillType', $this->drillType);
        $this->drillValue        = request('drillValue', $this->drillValue);

        $data     = $this->prepareReportData(applyDrill: (bool)$this->customerId);
        $summary  = $this->perCustomerRows();
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
            'customer-sales-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    protected function prepareReportData(bool $applyDrill = false)
    {
        $user   = Auth::user();
        $agency = $user->agency;
        $customer = $this->customerId ? Customer::find($this->customerId) : null;

        $query = ($applyDrill && $this->customerId) ? $this->operationsQuery() : $this->baseQuery();

        $sales = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->withSum('collections','amount')
            ->get();

        $totals = $this->computeTotals($sales);

        $byService = $sales->groupBy('service_type_id')->map(function ($group) {
            $sell   = (float) $group->sum('usd_sell');
            $buy    = (float) $group->sum('usd_buy');
            $profit = $sell - $buy;

            $paid = (float) $group->map(function ($s) {
                if (in_array($s->status, ['Refund-Full','Refund-Partial'])) return 0;
                return (float) ($s->amount_paid ?? 0)
                     + (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
            })->sum();

            return [
                'count'    => $group->count(),
                'sell'     => $sell,
                'buy'      => $buy,
                'profit'   => $profit,
                'remaining'=> $sell - $paid,
                'firstRow' => $group->first(),
            ];
        });

        $byMonth = $sales->groupBy(fn($s) => Carbon::parse($s->sale_date)->format('Y-m'))
            ->map(function ($group) {
                $sell   = (float) $group->sum('usd_sell');
                $buy    = (float) $group->sum('usd_buy');
                $profit = $sell - $buy;

                $paid = (float) $group->map(function ($s) {
                    if (in_array($s->status, ['Refund-Full','Refund-Partial'])) return 0;
                    return (float) ($s->amount_paid ?? 0)
                         + (float) ($s->collections_sum_amount ?? $s->collections->sum('amount'));
                })->sum();

                return [
                    'count'    => $group->count(),
                    'sell'     => $sell,
                    'buy'      => $buy,
                    'profit'   => $profit,
                    'remaining'=> $sell - $paid,
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
            'customer'  => $customer,
        ];
    }

    public function render()
    {
        $sales = $this->operationsQuery()
            ->withSum('collections','amount')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(12);

        $sales->each(function ($sale) {
            $paid = in_array($sale->status, ['Refund-Full','Refund-Partial'])
                ? 0
                : (float)($sale->amount_paid ?? 0) + (float)$sale->collections_sum_amount;

            $sale->remaining_payment = (float)($sale->usd_sell ?? 0) - $paid;
            $sale->effective_customer_commission = $this->effectiveCustomerCommission($sale);
        });

        $data = $this->prepareReportData();
        $perCustomer = $this->perCustomerRows();

        return view('livewire.agency.reportsView.customer-sales-report', [
            'sales'        => $sales,
            'totals'       => $data['totals'],
            'byService'    => $data['byService'],
            'byMonth'      => $data['byMonth'],
            'customers'    => $this->customers,
            'serviceTypes' => $this->serviceTypes,
            'providers'    => $this->providers,
            'perCustomer'  => $perCustomer,
        ]);
    }
}