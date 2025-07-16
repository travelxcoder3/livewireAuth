<?php 
namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;
use App\Models\ServiceType;
use App\Models\Provider;
use App\Models\DynamicListItem;
class Accounts extends Component
{
    use WithPagination;

    public $name, $account_number, $currency, $balance = 0, $note;
    public $editingId = null;
    public $search = '';
    public $confirmingDeletion = false;
    public $accountToDelete = null;

    public $serviceTypes = [];
    public $providers = [];
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // خصائص الفلاتر
    public $serviceTypeFilter = '';
    public $providerFilter = '';
    public $accountFilter = '';
    public $startDate = '';
    public $endDate = '';
    public $pnrFilter = '';
    public $referenceFilter = '';

// وهكذا ...


    protected $rules = [
        'name' => 'required|string|max:255',
        'account_number' => 'nullable|string|max:255',
        'currency' => 'required|string|max:3',
        'balance' => 'required|numeric',
        'note' => 'nullable|string',
    ];

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
        $this->currency = auth()->user()->agency->currency ?? 'USD';
  

        $this->serviceTypes = DynamicListItem::whereHas('list', function ($q) {
            $q->where('name', 'قائمة الخدمات')
            ->where(function ($query) {
                $query->whereNull('agency_id') // القوائم النظامية
                        ->orWhere('agency_id', auth()->user()->agency_id); // أو قوائم الوكالة
            });
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
            'message' => 'هل أنت متأكد من رغبتك في حذف هذا الحساب؟ لا يمكن التراجع عن هذه العملية.',
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
        $this->reset(['name', 'account_number', 'balance', 'note', 'editingId']);
        $this->currency = auth()->user()->agency->currency ?? 'USD';
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
    $user = Auth::user();
    $agency = $user->agency;

    // جلب الحسابات الخاصة بوكالة المستخدم الحالي فقط (كما هو)
    $accounts = Account::where('agency_id', $agency->id)
        ->latest()
        ->get();

    // تحديد الوكالات المطلوبة في العمليات
    if ($agency->parent_id) {
        // فرع: يعرض فقط عملياته
        $agencyIds = [$agency->id];
    } else {
        // وكالة رئيسية: يعرض عمليات الوكالة وكل الفروع التابعة لها
        $branchIds = $agency->branches()->pluck('id')->toArray();
        $agencyIds = array_merge([$agency->id], $branchIds);
    }

    $filteredSalesQuery = Sale::with(['serviceType', 'provider', 'account', 'agency'])
        ->whereIn('agency_id', $agencyIds)
        ->when($this->search, function ($query) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('beneficiary_name', 'like', $searchTerm)
                  ->orWhere('reference', 'like', $searchTerm)
                  ->orWhere('pnr', 'like', $searchTerm);
            });
        })
        ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
        ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
        ->when($this->accountFilter, fn($q) => $q->where('account_id', $this->accountFilter))
        ->when($this->pnrFilter, fn($q) => $q->where('pnr', 'like', '%'.$this->pnrFilter.'%'))
        ->when($this->referenceFilter, fn($q) => $q->where('reference', 'like', '%'.$this->referenceFilter.'%'))
        ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
        ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate));

    $totalSales = $filteredSalesQuery->clone()->sum('usd_sell'); // لحساب الإجمالي الصحيح
    $sales = $filteredSalesQuery->orderBy($this->sortField, $this->sortDirection)->paginate(10);

    return view('livewire.agency.accounts', compact('accounts', 'sales', 'totalSales'))
        ->layout('layouts.agency');
}

}