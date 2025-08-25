<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Invoice;
use Spatie\Browsershot\Browsershot;
use App\Models\User;

class InvoicesReview extends Component
{
    use WithPagination;

    public $numberSearch = '';
    public $entitySearch = '';
    public $userFilter   = '';
    public $startDate    = '';
    public $endDate      = '';
    public $sortField    = 'date';
    public $sortDirection= 'desc';

    public bool $showDetailsModal = false;
    public ?int $selectedInvoiceId = null;
    public $selectedInvoice = null;
    public array $userOptions = [];
    public ?string $userLabel = null;
    public string $userSearch = '';

    protected $queryString = [
        'numberSearch'   => ['except' => ''],
        'entitySearch'   => ['except' => ''],
        'userFilter'     => ['except' => ''],
        'startDate'      => ['except' => ''],
        'endDate'        => ['except' => ''],
        'sortField'      => ['except' => 'date'],
        'sortDirection'  => ['except' => 'desc'],
    ];

    public function updating($name, $value)
    {
        if (in_array($name, [
            'numberSearch','entitySearch','userFilter','startDate','endDate','sortField','sortDirection'
        ], true)) {
            $this->resetPage();
        }
    }

    private function baseQuery()
    {
        $agency = Auth::user()->agency;
        $agencyIds = $agency->parent_id ? [$agency->id] : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        return Invoice::with(['user','agency','sales.service'])
            ->withCount('sales')
            ->when($this->numberSearch, fn($q)=>$q->where('invoice_number','like','%'.$this->numberSearch.'%'))
            ->when($this->entitySearch, fn($q)=>$q->where('entity_name','like','%'.$this->entitySearch.'%'))
            ->when($this->userFilter !== '' && $this->userFilter !== null, fn($q)=>$q->where('user_id',(int)$this->userFilter))
            ->when($this->startDate, fn($q)=>$q->whereDate('date','>=',$this->startDate))
            ->when($this->endDate,   fn($q)=>$q->whereDate('date','<=',$this->endDate));
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

    public function openDetails($invoiceId)
    {
        $inv = Invoice::with(['user','agency','sales.service'])->findOrFail($invoiceId);
        $this->selectedInvoiceId = $inv->id;
        $this->selectedInvoice   = $inv;
        $this->showDetailsModal  = true;
    }

    public function downloadPdf($invoiceId)
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
            ->format('A4')->landscape()->margins(10,10,10,10)
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->savePdf($absolute);

        return response()->download($absolute);
    }

    public function resetFilters()
    {
        $this->reset(['numberSearch','entitySearch','userFilter','startDate','endDate']);
        $this->resetPage();
    }

    public function render()
    {
        $agency = Auth::user()->agency;
        $agencyIds = $agency->parent_id ? [$agency->id] : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $this->userOptions = User::whereIn('agency_id', $agencyIds)
            ->when($this->userSearch, fn($q)=>$q->where('name','like','%'.$this->userSearch.'%'))
            ->orderBy('name')
            ->limit(100)
            ->pluck('name','id')
            ->toArray();

        $this->userLabel = $this->userFilter ? optional(User::find($this->userFilter))->name : null;

        $invoices = $this->baseQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.agency.invoices-review', compact('invoices'))
            ->layout('layouts.agency');
    }
}
