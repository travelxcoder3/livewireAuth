<?php

namespace App\Livewire\Agency\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Provider;
use App\Models\DynamicListItem;
use Livewire\Attributes\Layout;
use App\Tables\AccountTable;
use App\Exports\AccountsReportExport;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class AccountsReport extends Component
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
    public $columns = [];

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
        $this->columns = AccountTable::columns();
    }

    public function exportToPdf()
    {
        dd('triggered');
        $data = $this->prepareReportData();

        $html = view('reports.accounts-full-pdf', [
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
                    'Content-Disposition' => 'inline; filename="accounts-full-report.pdf"',
                ]);
    }
    protected function safeUtf8($value)
    {
        if (is_null($value))
            return '';

        if (is_string($value)) {
            // إزالة الأحرف غير الصالحة
            $value = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $value);
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        return $value;
    }

    public function exportToExcel()
    {
        $data = $this->prepareReportData();

        return Excel::download(
            new AccountsReportExport([
                'sales' => $data['sales']
            ]),
            'accounts-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    protected function prepareReportData()
    {
        $agency = auth()->user()->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $sales = Sale::with(['service', 'provider', 'account', 'customer'])
            ->whereIn('agency_id', $agencyIds)
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
            ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate))
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
            'columns' => $this->columns,
            'totalSales' => $sales->sum('usd_sell')
        ];
    }

    protected function loadInitialData()
    {
        $this->serviceTypes = DynamicListItem::whereHas('list', function ($q) {
            $q->where('name', 'قائمة الخدمات')
                ->where(function ($query) {
                    $query->whereNull('agency_id')
                        ->orWhere('agency_id', auth()->user()->agency_id);
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
        $agency = auth()->user()->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $salesQuery = Sale::with(['service', 'provider', 'account', 'customer'])
            ->whereIn('agency_id', $agencyIds)
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
            ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate));

        $this->totalSales = $salesQuery->clone()->sum('usd_sell');
        $sales = $salesQuery->orderBy($this->sortField, $this->sortDirection)->paginate(10);

        return view('livewire.agency.reportsView.accounts-report', [
            'sales' => $sales,
            'totalSales' => $this->totalSales,
            'columns' => $this->columns,
            'serviceTypes' => $this->serviceTypes,
            'providers' => $this->providers,
            'customers' => $this->customers
        ]);
    }

    public function filteredSales()
    {
        return Sale::with(['customer', 'provider', 'serviceType'])
            ->where('agency_id', Auth::user()->agency_id)
            ->when($this->search, fn($q) => $q->where(function ($query) {
                $query->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('pnr', 'like', "%{$this->search}%")
                    ->orWhere('route', 'like', "%{$this->search}%");
            }))
            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
            ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }
}
