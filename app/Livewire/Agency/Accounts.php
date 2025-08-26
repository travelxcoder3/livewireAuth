<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Provider;
use App\Models\DynamicListItem;
use App\Models\Account;
use App\Models\Invoice;
use Spatie\Browsershot\Browsershot;

class Accounts extends Component
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

    /** فاتورة فردية */
    public bool $showInvoiceModal = false;
    public $selectedSale = null; // كائن بسيط لتفادي Hydration
    public float $taxAmount = 0.0;
    public bool  $taxIsPercent = true;
    public string $invoiceStep = 'tax';
    public ?int $currentInvoiceId = null;
    public array $invoiceTotals = ['base' => 0.0, 'tax' => 0.0, 'net' => 0.0];
    public bool $isCreditNote = false;

    /** فاتورة مجمّعة */
    public $selectAll = false;
    public $selectedSales = [];
    public array $visibleSaleIds = [];
    public $invoiceEntityName, $invoiceDate;
    public $showBulkInvoiceModal = false;
    public float $bulkTaxAmount = 0.0;
    public bool  $bulkTaxIsPercent = true;
    public float $bulkSubtotal = 0.0;

    protected $listeners = ['openInvoiceModal'];

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
         $agencyId = Auth::user()->agency_id;

        return Sale::with(['service','provider','account','customer','collections','user'])
            ->where('agency_id', $agencyId)

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

    private function recalcInvoiceTotals(): void
    {
        $base   = (float) data_get($this->selectedSale, 'usd_sell', 0);
        $status = (string) data_get($this->selectedSale, 'status', '');

        $this->isCreditNote = in_array($status, ['Refund-Full','Refund-Partial'], true);
        if ($this->isCreditNote) $base = -abs($base);

        $taxInput = round((float)$this->taxAmount, 2);

        $tax = $this->taxIsPercent
            ? round($base * ($taxInput / 100), 2)
            : ($this->isCreditNote ? -abs($taxInput) : abs($taxInput));

        $net = $base + $tax;
        $this->invoiceTotals = compact('base','tax','net');
    }

    private function latestInvoiceIdForSale(int $saleId): ?int
    {
        $inv = Invoice::whereHas('sales', fn ($q) => $q->where('sale_id', $saleId))
            ->latest('id')->first();
        return $inv?->id;
    }

    /* ================= Selection ================= */

    public function toggleSelectAll(): void
    {
        $currentPage = $this->getCurrentSales()->getCollection();
        $idsOnPage = $currentPage->pluck('id')->map(fn($id)=>(string)$id)->all();

        if (count($this->selectedSales) === count($idsOnPage)) {
            $this->selectedSales = array_values(array_diff($this->selectedSales, $idsOnPage));
        } else {
            $this->selectedSales = array_values(array_unique(array_merge($this->selectedSales, $idsOnPage)));
        }
    }

    public function updatedSelectedSales()
    {
        $sales = $this->getCurrentSales();
        $this->selectAll = count($this->selectedSales) === $sales->count();
    }

    public function getCurrentSales()
    {
        return $this->baseSalesQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    /* ================= Invoice Modal (Single) ================= */

    public function openInvoiceModal($saleId)
    {
        $sale = Sale::with(['customer','provider','account','agency','user','collections'])
            ->findOrFail($saleId);

        $paid = (float)(($sale->amount_paid ?? 0) + $sale->collections->sum('amount'));
        $rem  = (float)$sale->usd_sell - $paid;

        $data = $sale->toArray();
        $data['paid_total'] = $paid;
        $data['remaining']  = $rem;

        $this->selectedSale     = (object)$data;
        $this->taxIsPercent     = true;
        $this->taxAmount        = 0.0;
        $this->currentInvoiceId = $this->latestInvoiceIdForSale((int)$saleId);

        $this->invoiceStep = 'tax';
        $this->recalcInvoiceTotals();
        $this->showInvoiceModal = true;
    }

    public function addTax(): void
    {
        $saleId = (int) data_get($this->selectedSale, 'id', 0);
        if ($saleId <= 0) return;

        $this->taxAmount = max(0, (float)$this->taxAmount);
        $this->recalcInvoiceTotals();

        $sale = Sale::with('customer')->findOrFail($saleId);

        $base = round((float)$this->invoiceTotals['base'], 2);
        $tax  = round((float)$this->invoiceTotals['tax'],  2);
        $net  = round((float)$this->invoiceTotals['net'],  2);

        $prefix = $this->isCreditNote ? 'CN-' : 'INV-';

        $invoice = Invoice::updateOrCreate(
            ['invoice_number' => $prefix . str_pad($sale->id, 5, '0', STR_PAD_LEFT)],
            [
                'date'        => now()->toDateString(),
                'user_id'     => auth()->id(),
                'agency_id'   => $sale->agency_id,
                'entity_name' => $sale->customer->name ?? '—',
                'subtotal'    => $base,
                'tax_total'   => $tax,
                'grand_total' => $net,
            ]
        );

        $invoice->sales()->syncWithoutDetaching([
            $sale->id => [
                'base_amount'    => $base,
                'tax_is_percent' => $this->taxIsPercent ? 1 : 0,
                'tax_input'      => $this->taxIsPercent
                    ? round((float)$this->taxAmount, 2)
                    : ($this->isCreditNote ? -abs(round((float)$this->taxAmount, 2)) : abs(round((float)$this->taxAmount, 2)) ),
                'tax_amount'     => $tax,
                'line_total'     => $net,
            ],
        ]);

        $this->currentInvoiceId = $invoice->id;
        $this->invoiceStep = 'preview';
    }

    public function editTax(): void
    {
        $this->invoiceStep = 'tax';
    }

    public function downloadSingleInvoicePdf(int $invoiceId)
    {
        $invoice = Invoice::with([
            'sales.customer','sales.provider','sales.account','sales.agency','sales.user','sales.collections',
            'agency','user'
        ])->findOrFail($invoiceId);

        $sale = $invoice->sales->first();
        abort_if(!$sale, 404, 'No sale line found for invoice');

        $base = (float)($invoice->subtotal ?? 0);
        $tax  = (float)($invoice->tax_total ?? 0);
        $net  = (float)($invoice->grand_total ?? ($base + $tax));

        $html = view('invoices.sale-invoice', [
            'sale'    => $sale,
            'base'    => $base,
            'tax'     => $tax,
            'net'     => $net,
            'invoice' => $invoice,
        ])->render();

        $pdfPath  = 'pdfs/invoice-' . $invoice->id . '.pdf';
        $absolute = storage_path('app/public/' . $pdfPath);
        Storage::disk('public')->makeDirectory('pdfs');

        $chromePath = env('BROWSERSHOT_CHROME_PATH', PHP_OS_FAMILY === 'Windows'
            ? 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
            : '/usr/bin/google-chrome');

        Browsershot::html($html)
            ->setChromePath($chromePath)
            ->noSandbox()
            ->setOption('args', ['--disable-dev-shm-usage'])
            ->format('A4')->landscape()->margins(10, 10, 10, 10)
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->savePdf($absolute);

        return response()->download($absolute);
    }

    public function downloadInvoicePdf($saleId)
    {
        $invoiceId = $this->currentInvoiceId ?: $this->latestInvoiceIdForSale((int)$saleId);
        abort_if(!$invoiceId, 404, 'Invoice not found for this sale');
        return $this->downloadSingleInvoicePdf($invoiceId);
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
        $this->selectedSales = [];
        $this->selectAll = false;
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
            $this->selectedSales = [];
            $this->selectAll = false;
        }
    }

    /* ================= Bulk Invoice ================= */

    public function toggleSaleSelection($saleId)
    {
        if (($key = array_search($saleId, $this->selectedSales)) !== false) {
            unset($this->selectedSales[$key]);
        } else {
            $this->selectedSales[] = $saleId;
        }
    }

    public function openBulkInvoiceModal()
    {
        if (empty($this->selectedSales)) {
            session()->flash('message', 'يرجى اختيار عمليات بيع أولاً');
            return;
        }

        $this->bulkTaxAmount = 0.0;
        $this->bulkTaxIsPercent = true;

        $sales = Sale::whereIn('id', $this->selectedSales)->get();
        $this->bulkSubtotal = 0.0;
        foreach ($sales as $s) {
            $b = (float)$s->usd_sell;
            if (in_array($s->status ?? '', ['Refund-Full','Refund-Partial'])) $b = -abs($b);
            $this->bulkSubtotal += $b;
        }

        $this->invoiceEntityName = '';
        $this->invoiceDate = now()->toDateString();
        $this->showBulkInvoiceModal = true;
    }

    public function render()
    {
        $customers = Customer::where('agency_id', Auth::user()->agency_id)->latest()->get();

        $salesQuery = $this->baseSalesQuery();
        $totalSales = (clone $salesQuery)->sum('usd_sell');

        $this->sales = $salesQuery->orderBy($this->sortField, $this->sortDirection)->paginate(10);
        $this->exportSales = $this->sales->getCollection();

        foreach ($this->sales as $sale) {
            $sale->paid_total = ($sale->amount_paid ?? 0) + $sale->collections->sum('amount');
            $sale->remaining  = $sale->usd_sell - $sale->paid_total;
        }

        $this->visibleSaleIds = $this->exportSales->pluck('id')->toArray();

        return view('livewire.agency.accounts', [
            'customers'  => $customers,
            'sales'      => $this->sales,
            'totalSales' => $totalSales,
        ])->layout('layouts.agency');
    }

    public function downloadBulkInvoicePdf($invoiceId)
    {
        $invoice = Invoice::with(['sales','agency','user'])->findOrFail($invoiceId);

        $html = view('invoices.bulk-invoice', ['invoice' => $invoice])->render();

        $pdfPath  = 'pdfs/bulk-invoice-' . $invoice->id . '.pdf';
        $absolute = storage_path('app/public/' . $pdfPath);
        Storage::disk('public')->makeDirectory('pdfs');

        $chromePath = env('BROWSERSHOT_CHROME_PATH', PHP_OS_FAMILY === 'Windows'
            ? 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
            : '/usr/bin/google-chrome');

        Browsershot::html($html)
            ->setChromePath($chromePath)
            ->noSandbox()
            ->setOption('args', ['--disable-dev-shm-usage'])
            ->format('A4')->landscape()->margins(10, 10, 10, 10)
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->savePdf($absolute);

        return response()->download($absolute);
    }

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

        return DB::transaction(function () {
            $user   = auth()->user();
            $agency = $user->agency;

            $sales = Sale::whereIn('id', $this->selectedSales)->get();
            $subtotal = 0.0;
            foreach ($sales as $s) {
                $b = (float)$s->usd_sell;
                if (in_array($s->status ?? '', ['Refund-Full','Refund-Partial'])) $b = -abs($b);
                $subtotal += $b;
            }

            $tax  = $this->bulkTaxIsPercent
                ? round($subtotal * ($this->bulkTaxAmount / 100), 2)
                : round((float)$this->bulkTaxAmount, 2);

            $grand = $subtotal + $tax;

            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . rand(100, 999),
                'entity_name'    => $this->invoiceEntityName,
                'date'           => $this->invoiceDate,
                'user_id'        => $user->id,
                'agency_id'      => $agency->id,
                'subtotal'       => $subtotal,
                'tax_total'      => $tax,
                'grand_total'    => $grand,
            ]);

            $attachData = [];
            $sumSoFar = 0.0;
            $count = $sales->count();
            $i = 0;
            $percent = $this->bulkTaxIsPercent ? ($this->bulkTaxAmount / 100) : null;

            foreach ($sales as $s) {
                $i++;
                $base = (float)$s->usd_sell;
                if (in_array($s->status ?? '', ['Refund-Full','Refund-Partial'])) $base = -abs($base);

                if ($this->bulkTaxIsPercent) {
                    $lineTax = round($base * $percent, 2);
                } else {
                    $weight  = $subtotal != 0.0 ? ($base / $subtotal) : 0.0;
                    $lineTax = round($tax * $weight, 2);
                }

                if ($i === $count) $lineTax = round($tax - $sumSoFar, 2);
                $sumSoFar += $lineTax;

                $attachData[$s->id] = [
                    'base_amount'    => $base,
                    'tax_is_percent' => $this->bulkTaxIsPercent ? 1 : 0,
                    'tax_input'      => $this->bulkTaxIsPercent ? (float)$this->bulkTaxAmount : $lineTax,
                    'tax_amount'     => $lineTax,
                    'line_total'     => $base + $lineTax,
                ];
            }

            $invoice->sales()->syncWithoutDetaching($attachData);

            $this->showBulkInvoiceModal = false;
            $this->selectedSales = [];

            return $this->downloadBulkInvoicePdf($invoice->id);
        });
    }
}
