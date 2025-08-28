<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Provider;
use App\Models\DynamicListItem;
use App\Models\Account;

class SalesReview extends Component
{
    use WithPagination;

    /** حسابات */
    public $name, $account_number, $currency, $balance = 0, $note;
    public $editingId = null;

    /** فلاتر/فرز */
    public $employeeSearch = '';
    public $serviceTypes = [];
    public $providers = [];
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $serviceTypeFilter = '';
    public $providerFilter    = '';
    public $accountFilter     = '';
    public $startDate = '';
    public $endDate   = '';
    public $pnrFilter = '';
    public $referenceFilter = '';

    /** حذف */
    public $confirmingDeletion = false;
    public $accountToDelete = null;


    protected $rules = [
        'name' => 'required|string|max:255',
        'account_number' => 'nullable|string|max:255',
        'currency' => 'required|string|max:3',
        'balance' => 'required|numeric',
        'note' => 'nullable|string',
    ];

    protected $queryString = [
        'employeeSearch'    => ['except' => ''],
        'serviceTypeFilter' => ['except' => ''],
        'providerFilter'    => ['except' => ''],
        'accountFilter'     => ['except' => ''],
        'startDate'         => ['except' => ''],
        'endDate'           => ['except' => ''],
        'pnrFilter'         => ['except' => ''],
        'referenceFilter'   => ['except' => ''],
        'sortField'         => ['except' => 'created_at'],
        'sortDirection'     => ['except' => 'desc'],
    ];

    /* ================= Helpers ================= */

    private function baseSalesQuery()
    {
        $agency = Auth::user()->agency;
        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        return Sale::with(['service','provider','account','customer','collections','user'])
            ->whereIn('agency_id', $agencyIds)
            ->when($this->employeeSearch, function ($q) {
                $s = '%'.$this->employeeSearch.'%';
                $q->whereHas('user', fn($uq) => $uq->where('name','like',$s));
            })
            ->when($this->serviceTypeFilter !== '' && $this->serviceTypeFilter !== null, fn($q)=>$q->where('service_type_id', (int)$this->serviceTypeFilter))
            ->when($this->providerFilter    !== '' && $this->providerFilter    !== null, fn($q)=>$q->where('provider_id', (int)$this->providerFilter))
            ->when($this->accountFilter     !== '' && $this->accountFilter     !== null, fn($q)=>$q->where('customer_id', (int)$this->accountFilter))
            ->when($this->pnrFilter,       fn($q)=>$q->where('pnr','like','%'.$this->pnrFilter.'%'))
            ->when($this->referenceFilter, fn($q)=>$q->where('reference','like','%'.$this->referenceFilter.'%'))
            ->when($this->startDate,       fn($q)=>$q->whereDate('sale_date','>=',$this->startDate))
            ->when($this->endDate,         fn($q)=>$q->whereDate('sale_date','<=',$this->endDate));
    }

    public function getCurrentSales()
    {
        return $this->baseSalesQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    /* ================= CRUD Accounts ================= */

    public function mount()
    {
        $this->currency = auth()->user()->agency->currency ?? 'USD';

        $this->serviceTypes = DynamicListItem::whereHas('list', function ($q) {
            $q->where('name', 'قائمة الخدمات')
              ->where(fn($qq)=>$qq->where('created_by_agency', auth()->user()->agency_id)->orWhereNull('created_by_agency'));
        })->orderBy('order')->get();

        $this->providers = Provider::where('agency_id', auth()->user()->agency_id)->get();
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

    public function save()
    {
        $this->validate();
        Account::create([
            'name' => $this->name,
            'account_number' => $this->account_number,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'note' => $this->note,
            'agency_id' => Auth::user()->agency_id,
        ]);
        $this->resetForm();
        session()->flash('message', 'تم إضافة الحساب بنجاح');
    }

    public function edit($id)
    {
        $account = Account::findOrFail($id);
        $this->editingId = $id;
        $this->name = $account->name;
        $this->account_number = $account->account_number;
        $this->currency = $account->currency;
        $this->balance = $account->balance;
        $this->note = $account->note;
    }

    public function update()
    {
        $this->validate();
        $account = Account::findOrFail($this->editingId);
        $account->update([
            'name' => $this->name,
            'account_number' => $this->account_number,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'note' => $this->note,
        ]);
        $this->resetForm();
        session()->flash('message', 'تم تحديث الحساب بنجاح');
    }

    public function confirmDelete($id)
    {
        $this->accountToDelete = $id;
        $this->dispatch('showConfirmationModal', [
            'title' => 'تأكيد الحذف',
            'message' => 'هل أنت متأكد من رغبتك في حذف هذا الحساب؟',
            'action' => 'performDelete',
            'confirmText' => 'حذف',
            'cancelText' => 'إلغاء'
        ]);
    }

    public function performDelete()
    {
        if ($this->accountToDelete) {
            $account = Account::findOrFail($this->accountToDelete);
            $account->delete();
            session()->flash('message', 'تم حذف الحساب بنجاح');
            $this->accountToDelete = null;
        }
    }

    public function resetForm()
    {
        $this->reset(['name','account_number','balance','note','editingId']);
        $this->currency = auth()->user()->agency->currency ?? 'USD';
    }

    public function resetFilters()
    {
        $this->reset([
            'employeeSearch','serviceTypeFilter','providerFilter','accountFilter',
            'startDate','endDate','pnrFilter','referenceFilter'
        ]);
        $this->resetPage();
    }

    /** إعادة ضبط الصفحات عند تغيير أي فلتر/فرز */
    public function updating($name, $value)
    {
        $filters = [
            'employeeSearch','serviceTypeFilter','providerFilter','accountFilter',
            'startDate','endDate','pnrFilter','referenceFilter','sortField','sortDirection'
        ];
        if (in_array($name, $filters, true)) {
            $this->resetPage();
        }
    }

public function render()
{
    $customers = Customer::where('agency_id', Auth::user()->agency_id)->latest()->get();

    $salesQuery = $this->baseSalesQuery();
    $totalSales = (clone $salesQuery)->sum('usd_sell');

    // لا نستخدم $this->sales
    $sales = $salesQuery->orderBy($this->sortField, $this->sortDirection)->paginate(10);

    foreach ($sales as $sale) {
        $sale->paid_total = ($sale->amount_paid ?? 0) + $sale->collections->sum('amount');
        $sale->remaining  = $sale->usd_sell - $sale->paid_total;
    }

    return view('livewire.agency.sales-review', [
        'customers'  => $customers,
        'sales'      => $sales,
        'totalSales' => $totalSales,
    ])->layout('layouts.agency');
}

}
