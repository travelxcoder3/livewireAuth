<?php
namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;
use App\Models\ServiceType;
use App\Models\Provider;
use App\Models\DynamicListItem;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;
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
    public $showInvoiceModal = false;
    public $selectedSale;
public $selectAll = false;
public $selectedSales = [];


public function updatedSelectedSales()
{
    $sales = $this->getCurrentSales();
    $this->selectAll = count($this->selectedSales) === $sales->count();
}


public function getCurrentSales()
{
    $agency = Auth::user()->agency;

    $agencyIds = $agency->parent_id
        ? [$agency->id]
        : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

    return Sale::whereIn('agency_id', $agencyIds)
        ->when($this->search, fn($q) => $q->where('beneficiary_name', 'like', '%' . $this->search . '%'))
        ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
        ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
        ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
        ->when($this->pnrFilter, fn($q) => $q->where('pnr', 'like', '%' . $this->pnrFilter . '%'))
        ->when($this->referenceFilter, fn($q) => $q->where('reference', 'like', '%' . $this->referenceFilter . '%'))
        ->when($this->startDate, fn($q) => $q->whereDate('sale_date', '>=', $this->startDate))
        ->when($this->endDate, fn($q) => $q->whereDate('sale_date', '<=', $this->endDate))
        ->orderBy($this->sortField, $this->sortDirection)
        ->paginate(10);
}



    protected $listeners = ['openInvoiceModal'];
    public function openInvoiceModal($saleId)
    {
        $this->selectedSale = \App\Models\Sale::with(['customer', 'provider', 'account'])->findOrFail($saleId);
        $this->showInvoiceModal = true;
    }



    public function downloadInvoicePdf($saleId)
    {
        $sale = \App\Models\Sale::with(['service', 'provider', 'account', 'customer'])->findOrFail($saleId);

        $html = view('invoices.sale-invoice', ['sale' => $sale])->render();

        $pdfPath = 'pdfs/invoice-' . $sale->id . '.pdf';
        Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->waitUntilNetworkIdle()
            ->save(storage_path('app/public/' . $pdfPath));

        return response()->download(storage_path('app/public/' . $pdfPath));
    }


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
                    $query->where('created_by_agency', auth()->user()->agency_id)
                        ->orWhereNull('created_by_agency');
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

    public $invoiceEntityName, $invoiceDate;
    public $showBulkInvoiceModal = false;

    // اختيار/إلغاء اختيار عملية بيع
    public function toggleSaleSelection($saleId)
    {
        if (($key = array_search($saleId, $this->selectedSales)) !== false) {
            unset($this->selectedSales[$key]);
        } else {
            $this->selectedSales[] = $saleId;
        }
    }

    // فتح نافذة الفاتورة المجمعة
    public function openBulkInvoiceModal()
    {
        if (empty($this->selectedSales)) {
            session()->flash('message', 'يرجى اختيار عمليات بيع أولاً');
            return;
        }
        $this->invoiceEntityName = '';
        $this->invoiceDate = now()->toDateString();
        $this->showBulkInvoiceModal = true;
    }

    public function render()
{
    $user = Auth::user();
    $agency = $user->agency;

    // جلب الحسابات الخاصة بوكالة المستخدم الحالي فقط (كما هو)
    $customers = Customer::where('agency_id', $agency->id)
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

    $filteredSalesQuery = Sale::with(['service', 'provider', 'account', 'customer', 'collections'])
        ->whereIn('agency_id', $agencyIds)
        ->when(!$user->hasRole('agency-admin'), function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
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
        ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
        ->when($this->pnrFilter, fn($q) => $q->where('pnr', 'like', '%' . $this->pnrFilter . '%'))
        ->when($this->referenceFilter, fn($q) => $q->where('reference', 'like', '%' . $this->referenceFilter . '%'))
        ->when($this->startDate, fn($q) => $q->whereDate('sale_date', '>=', $this->startDate))
        ->when($this->endDate, fn($q) => $q->whereDate('sale_date', '<=', $this->endDate));

    $totalSales = $filteredSalesQuery->clone()->sum('usd_sell');
    $this->sales = $filteredSalesQuery->orderBy($this->sortField, $this->sortDirection)->paginate(10);

    foreach ($this->sales as $sale) {
        $sale->paid_total = ($sale->amount_paid ?? 0) + $sale->collections->sum('amount');
        $sale->remaining = $sale->usd_sell - $sale->paid_total;
    }

    return view('livewire.agency.accounts', [
        'customers' => $customers,
        'sales' => $this->sales,
        'totalSales' => $totalSales,
    ])->layout('layouts.agency');
}

    // في ملف App\Livewire\Agency\Accounts.php
    public function downloadBulkInvoicePdf($invoiceId)
    {
        $invoice = \App\Models\Invoice::with(['sales', 'agency'])->findOrFail($invoiceId);

        $html = view('invoices.bulk-invoice', ['invoice' => $invoice])->render();

        $pdfPath = 'pdfs/bulk-invoice-' . $invoice->id . '.pdf';
        Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->waitUntilNetworkIdle()
            ->save(storage_path('app/public/' . $pdfPath));

        return response()->download(storage_path('app/public/' . $pdfPath));
    }

    // تحديث دالة createBulkInvoice لإرجاع معرف الفاتورة
    public function createBulkInvoice()
    {
        $this->validate([
            'invoiceEntityName' => 'required|string|max:255',
            'invoiceDate' => 'required|date',
        ]);

        if (empty($this->selectedSales)) {
            session()->flash('message', 'يرجى اختيار عمليات بيع');
            return;
        }

        $user = auth()->user();
        $agency = $user->agency;
        $invoiceNumber = 'INV-' . now()->format('YmdHis') . '-' . rand(100, 999);

        $invoice = \App\Models\Invoice::create([
            'invoice_number' => $invoiceNumber,
            'entity_name' => $this->invoiceEntityName,
            'date' => $this->invoiceDate,
            'user_id' => $user->id,
            'agency_id' => $agency->id,
        ]);

        $invoice->sales()->attach($this->selectedSales);

        $this->showBulkInvoiceModal = false;

        // تحميل الفاتورة مباشرة بعد الإنشاء
        return $this->downloadBulkInvoicePdf($invoice->id);
    }


    public function toggleSelectAll()
{
    $sales = $this->getCurrentSales();
    $this->selectedSales = $this->selectAll ? $sales->pluck('id')->toArray() : [];
}



}
