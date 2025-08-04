<?php

namespace App\Livewire\Agency\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Provider;
use App\Models\DynamicListItem;
use Livewire\Attributes\Layout;
use App\Exports\SalesReportExport;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;
use App\Tables\SalesTable;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class SalesReport extends Component
{
    use WithPagination;

    // خصائص الفلاتر
    public $search = '';
    public $serviceTypeFilter = '';
    public $providerFilter = '';
    public $accountFilter = '';
    public $startDate = '';
    public $endDate = '';
    public $pnrFilter = '';
    public $referenceFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    // بيانات إضافية
    public $serviceTypes = [];
    public $providers = [];
    public $customers = [];
    public $totalSales = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'serviceTypeFilter' => ['except' => ''],
        'providerFilter' => ['except' => ''],
        'accountFilter' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'pnrFilter' => ['except' => ''],
        'referenceFilter' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->loadInitialData();
    }

    public function exportToPdf()
    {
        $data = $this->prepareReportData();

        $html = view('reports.sales-full-pdf', [
            'sales' => $data['sales'],
            'totalSales' => $data['totalSales'],
            'agency' => $data['agency'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
        ])->render();

        return response(
            Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->emulateMedia('screen')
                ->noSandbox()
                ->waitUntilNetworkIdle()
                ->pdf()
        )->withHeaders([
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="sales-full-report.pdf"',
                ]);
    }

    public function exportToExcel()
    {
        $data = $this->prepareReportData();
        $currency = $data['agency']->currency ?? 'USD'; // احصل على عملة الوكالة

        return Excel::download(
            new SalesReportExport([
                'sales' => $data['sales'],
                'currency' => $currency, // مرر العملة هنا
            ]),
            'sales-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    protected function prepareReportData()
    {
        $user = auth()->user(); // ✅ أضف هذا السطر أولاً
        $agency = $user->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $sales = Sale::with(['service', 'provider', 'account', 'customer'])
            ->whereIn('agency_id', $agencyIds)
            ->when(!$user->hasAnyRole(['agency-admin']), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($this->search, function ($query) {
                $term = '%' . $this->search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('beneficiary_name', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('pnr', 'like', $term);
                });
            })
            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
            ->when($this->pnrFilter, fn($q) => $q->where('pnr', 'like', '%' . $this->pnrFilter . '%'))
            ->when($this->referenceFilter, fn($q) => $q->where('reference', 'like', '%' . $this->referenceFilter . '%'))
            ->when($this->startDate, fn($q) => $q->whereDate('sale_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('sale_date', '<=', $this->endDate))
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        return [
            'agency' => $agency,
            'sales' => $sales,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'filters' => [
                'search' => $this->search,
                'serviceTypeFilter' => $this->serviceTypeFilter,
                'providerFilter' => $this->providerFilter,
                'accountFilter' => $this->accountFilter,
                'pnrFilter' => $this->pnrFilter,
                'referenceFilter' => $this->referenceFilter,
            ],
            'totalSales' => $sales->sum('usd_sell')
        ];
    }


    protected function loadInitialData()
    {
        $this->serviceTypes = DynamicListItem::whereHas('list', function ($q) {
            $q->where('name', 'قائمة الخدمات')
                ->where(function ($query) {
                    $query->where('created_by_agency', auth()->user()->agency_id)
                        ->orWhereNull('created_by_agency');
                });
        })->orderBy('order')->get();

        $this->providers = Provider::where('agency_id', auth()->user()->agency_id)->get();
        $this->customers = Customer::where('agency_id', auth()->user()->agency_id)->latest()->get();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'serviceTypeFilter',
            'providerFilter',
            'accountFilter',
            'startDate',
            'endDate',
            'pnrFilter',
            'referenceFilter'
        ]);
    }

    public function render()
    {
        $user = auth()->user(); // ✅ تأكد من تعريف المستخدم أولاً
        $agency = $user->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $query = Sale::with(['user', 'service', 'provider', 'account', 'customer', 'collections'])
            ->whereIn('agency_id', $agencyIds)
            ->when(!$user->hasAnyRole(['agency-admin']), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($this->search, fn($q) => $q->where(function ($q2) {
                $q2->where('beneficiary_name', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%")
                    ->orWhere('pnr', 'like', "%{$this->search}%");
            }))
            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
            ->when($this->pnrFilter, fn($q) => $q->where('pnr', 'like', "%{$this->pnrFilter}%"))
            ->when($this->referenceFilter, fn($q) => $q->where('reference', 'like', "%{$this->referenceFilter}%"))
            ->when($this->startDate, fn($q) => $q->whereDate('sale_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('sale_date', '<=', $this->endDate));

        $this->totalSales = (clone $query)->sum('usd_sell');

        $sales = $query
            ->withSum('collections', 'amount')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        $sales->each(function ($sale) use ($agencyIds) {
            if ($sale->sale_group_id) {
                $groupedSales = Sale::with('collections')
                    ->whereIn('agency_id', $agencyIds)
                    ->where('sale_group_id', $sale->sale_group_id)
                    ->get();

                $paidFromSales = $groupedSales->sum('amount_paid');
                $paidFromCollections = $groupedSales->flatMap->collections->sum('amount');
            } else {
                $paidFromSales = $sale->amount_paid ?? 0;
                $paidFromCollections = $sale->collections->sum('amount');
            }

            $sale->total_paid = $paidFromSales + $paidFromCollections;
            $sale->remaining_payment = ($sale->usd_sell ?? 0) - $sale->total_paid;
        });

        return view('livewire.agency.reportsView.sales-report', [
            'sales' => $sales,
            'columns' => SalesTable::columns(true, true),
            'totalSales' => $this->totalSales,
            'serviceTypes' => $this->serviceTypes,
            'providers' => $this->providers,
            'customers' => $this->customers,
        ]);
    }


    public function printPdf($saleId)
    {
        $sale = Sale::with(['customer', 'provider', 'user', 'service'])->findOrFail($saleId);

        $html = view('reports.sale-single', compact('sale'))->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->landscape()
            ->margins(10, 10, 10, 10)
            ->pdf();

        return response()->streamDownload(
            fn() => print ($pdf),
            'sale-details-' . $sale->id . '.pdf'
        );
    }
}
