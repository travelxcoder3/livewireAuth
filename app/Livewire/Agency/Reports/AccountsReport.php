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
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use App\Exports\AccountsReportStreamExport;
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

    public $serviceTypes = [];

    // قوائم ديناميكية خفيفة
    public string $providerSearch = '';
    public array  $providerOptions = [];
    public string $customerSearch = '';
    public array  $customerOptions = [];
    public string $providerLabel = '';
    public string $customerLabel = '';

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

    public function refreshProviderOptions(){
    $t = trim($this->providerSearch);
    $this->providerOptions = Provider::query()
        ->where('agency_id', auth()->user()->agency_id)
        ->when($t !== '', function($q) use ($t){
            $like = mb_strlen($t) >= 3 ? "%$t%" : "$t%";
            $q->where('name','like',$like);
        })
        ->select('id','name')->orderBy('name')->limit(20)
        ->pluck('name','id')->toArray();
}
public function refreshCustomerOptions(){
    $t = trim($this->customerSearch);
    $this->customerOptions = Customer::query()
        ->where('agency_id', auth()->user()->agency_id)
        ->when($t !== '', function($q) use ($t){
            $like = mb_strlen($t) >= 3 ? "%$t%" : "$t%";
            $q->where('name','like',$like);
        })
        ->select('id','name')->orderBy('name')->limit(20)
        ->pluck('name','id')->toArray();
}
public function updatedProviderSearch(){ $this->refreshProviderOptions(); $this->skipRender(); }
public function updatedCustomerSearch(){ $this->refreshCustomerOptions(); $this->skipRender(); }

public function mount()
{
    $this->loadInitialData(); // تبقى لأن فيها serviceTypes
    $this->refreshProviderOptions();
    $this->refreshCustomerOptions();
    $this->columns = AccountTable::columns();
}




public function exportToExcel()
{
    return (new AccountsReportStreamExport($this->filters()))
        ->download('accounts-report.xlsx');
}

protected function filters(): array
{
    return [
        'search'        => $this->search ?: null,
        'service_type'  => $this->serviceTypeFilter ?: null,
        'provider'      => $this->providerFilter ?: null,
        'account'       => $this->accountFilter ?: null,
        'start'         => $this->startDate ?: null,
        'end'           => $this->endDate ?: null,
    ];
}


    protected function prepareReportData()
    {
        $user = auth()->user();
        $agency = $user->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $sales = Sale::with(['user','collections', 'service', 'provider', 'account', 'customer'])
            ->whereIn('agency_id', $agencyIds)
            ->when(!$user->hasRole('agency-admin'), fn($q) => $q->where('user_id', $user->id))
            ->when($this->search, function ($query) {
                $term = '%' . $this->search . '%';
                $query->whereHas('user', fn($u) => $u->where('name', 'like', $term));
            })

            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
            ->when($this->pnrFilter, fn($q) => $q->where('pnr', 'like', '%' . $this->pnrFilter . '%'))
            ->when($this->referenceFilter, fn($q) => $q->where('reference', 'like', '%' . $this->referenceFilter . '%'))
            ->when($this->startDate && $this->endDate,
                fn($q)=>$q->whereBetween('sale_date', [$this->startDate, $this->endDate]))
            ->when($this->startDate && !$this->endDate,
                fn($q)=>$q->where('sale_date','>=',$this->startDate))
            ->when(!$this->startDate && $this->endDate,
                fn($q)=>$q->where('sale_date','<=',$this->endDate))

            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        foreach ($sales as $sale) {
            $sale->paid_total = ($sale->amount_paid ?? 0) + $sale->collections->sum('amount');
            $sale->remaining = $sale->usd_sell - $sale->paid_total;
        }

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
            'columns' => $this->columns ?? [],
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
        $user = auth()->user();
        $agency = $user->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

$salesQuery = Sale::query()
    ->select([
        'id','agency_id','user_id','provider_id','service_type_id','customer_id',
        'beneficiary_name','route','status','duplicated_by','sale_group_id',
        'amount_paid','usd_sell','sale_date','service_date','pnr','reference','created_at',
    ])
    ->with([
        'user:id,name',
        'provider:id,name',
        'service:id,label',
        'customer:id,name',
        'duplicatedBy:id,name',   
    ])
    ->withSum('collections','amount')
            ->whereIn('agency_id', $agencyIds)
            ->when($this->search, function ($query) {
                $term = '%' . $this->search . '%';
                $query->whereHas('user', fn($u) => $u->where('name', 'like', $term));
            })

            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
      ->when($this->pnrFilter, function($q){
    $t = trim($this->pnrFilter);
    $like = mb_strlen($t) >= 3 ? "%$t%" : "$t%";
    $q->where('pnr','like',$like);
})
->when($this->referenceFilter, function($q){
    $t = trim($this->referenceFilter);
    $like = mb_strlen($t) >= 3 ? "%$t%" : "$t%";
    $q->where('reference','like',$like);
})

->when($this->startDate && $this->endDate,
    fn($q)=>$q->whereBetween('sale_date', [$this->startDate, $this->endDate]))
->when($this->startDate && !$this->endDate,
    fn($q)=>$q->where('sale_date','>=',$this->startDate))
->when(!$this->startDate && $this->endDate,
    fn($q)=>$q->where('sale_date','<=',$this->endDate));

        $this->totalSales = $salesQuery->clone()->sum('usd_sell');

        $sales = $salesQuery->orderBy($this->sortField, $this->sortDirection)->simplePaginate(10);

        foreach ($sales as $sale) {
            $sale->paid_total = ($sale->amount_paid ?? 0) + $sale->collections->sum('amount');
            $sale->remaining = $sale->usd_sell - $sale->paid_total;
        }

        return view('livewire.agency.reportsView.accounts-report', [
            'sales' => $sales,
            'totalSales' => $this->totalSales,
            'columns' => $this->columns,
            'serviceTypes' => $this->serviceTypes,
            'providerOptions' => $this->providerOptions,
            'customerOptions' => $this->customerOptions,
        ]);

    }

    public function filteredSales()
    {
        return Sale::with(['user','customer', 'provider', 'serviceType'])
            ->where('agency_id', Auth::user()->agency_id)
            ->when($this->search, fn($q) =>
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%"))
            )

            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
->when($this->startDate && $this->endDate,
    fn($q)=>$q->whereBetween('sale_date', [$this->startDate, $this->endDate]))
->when($this->startDate && !$this->endDate,
    fn($q)=>$q->where('sale_date','>=',$this->startDate))
->when(!$this->startDate && $this->endDate,
    fn($q)=>$q->where('sale_date','<=',$this->endDate))

            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }

    public function printPdf($saleId)
    {
        $sale = Sale::with(['customer', 'provider', 'user', 'service'])->findOrFail($saleId);

        $html = view('reports.account-single', compact('sale'))->render();

        $pdf = \Spatie\Browsershot\Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->pdf();

        return response()->streamDownload(
            fn() => print ($pdf),
            'account-details-' . $sale->id . '.pdf'
        );
    }

}

