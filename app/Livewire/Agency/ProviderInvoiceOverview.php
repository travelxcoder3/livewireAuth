<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Spatie\Browsershot\Browsershot;

class ProviderInvoiceOverview extends Component
{
    public Provider $provider;

    public $allGroups;
    public $groups;

    public array $selectedGroups = [];
    public string $search = '';
    public string $fromDate = '';
    public string $toDate   = '';

    public $activeRow = null;

    /** فاتورة فردية */
    public bool $showInvoiceModal = false;
    public ?string $currentGroupKey = null;
    public float $taxAmount = 0.0;
    public bool $taxIsPercent = true;
    public bool $isCreditNote = false;
    public array $invoiceTotals = ['base'=>0.0,'tax'=>0.0,'net'=>0.0];
    public ?int $currentInvoiceId = null;

    /** فاتورة مجمّعة */
    public bool $showBulkInvoiceModal = false;
    public string $invoiceEntityName = '';
    public string $invoiceDate = '';
    public float $bulkTaxAmount = 0.0;
    public bool $bulkTaxIsPercent = true;
    public float $bulkSubtotal = 0.0;

    public ?string $toastMessage = null;
    public ?string $toastType    = null;

    private function clearToast(): void
    {
        $this->toastMessage = null;
        $this->toastType    = null;
    }

    public function mount(Provider $provider)
    {
        abort_unless($provider->agency_id === Auth::user()->agency_id, 403);
        $this->provider = $provider;

        $this->allGroups = $this->buildGroups($provider);
        $this->applyFilters();
    }

    protected function buildGroups(Provider $provider)
    {
        $sales   = $provider->sales()->with(['collections','service','customer'])->get();
        $grouped = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

        return $grouped->map(function ($sales) {
            $s = $sales->sortByDesc('created_at')->first();

            $refundStatuses = [
                'refund-full','refund_full','refund-partial','refund_partial','refunded','refund'
            ];
            $voidStatuses = ['void','cancel','canceled','cancelled'];

            $costTotalTrue = $sales->reject(function ($x) use ($refundStatuses, $voidStatuses) {
                    $st = mb_strtolower(trim($x->status ?? ''));
                    return in_array($st, $refundStatuses, true) || in_array($st, $voidStatuses, true);
                })
                ->sum('usd_buy');

            $refundTotal = $sales->filter(function ($x) use ($refundStatuses, $voidStatuses) {
                    $st = mb_strtolower(trim($x->status ?? ''));
                    return in_array($st, $refundStatuses, true) || in_array($st, $voidStatuses, true);
                })
                ->sum(fn($x) => abs($x->usd_buy));

            $netCost = $costTotalTrue - $refundTotal;

            return (object)[
                'group_key'        => (string)($s->sale_group_id ?? $s->id),
                'beneficiary_name' => $s->beneficiary_name ?? optional($s->customer)->name ?? '—',
                'sale_date'        => $s->sale_date,
                'service_label'    => $s->service->label ?? '-',

                'cost_total_true'  => (float)$costTotalTrue,
                'refund_total'     => (float)$refundTotal,
                'net_cost'         => (float)$netCost, // أساس الفاتورة الفردية

                'scenarios' => $sales->map(function ($sale) {
                    return [
                        'id'          => $sale->id,
                        'group_id'    => $sale->sale_group_id,
                        'date'        => $sale->sale_date,
                        'usd_buy'     => $sale->usd_buy,
                        'status'      => $sale->status,
                        'note'        => $sale->reference ?? '-',
                    ];
                }),
            ];
        })->values();
    }

    protected function normalize(string $s): string
    {
        $s = str_replace('ـ','', $s);
        $map = ['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''];
        return mb_strtolower(strtr(trim($s), $map));
    }

    public function applyFilters(): void
    {
        $term = $this->normalize((string)$this->search);
        $from = $this->fromDate ?: '0001-01-01';
        $to   = $this->toDate   ?: '9999-12-31';

        $filtered = collect($this->allGroups)->filter(function($row) use ($term,$from,$to){
            $okName = $term === '' ? true
                : mb_strpos($this->normalize((string)($row->beneficiary_name ?? '')), $term) !== false;
            $date = (string)($row->sale_date ?? '');
            $okDate = ($date >= $from && $date <= $to);
            return $okName && $okDate;
        })->values();

        $this->groups = $filtered;

        $visibleKeys = $filtered->pluck('group_key')->all();
        $this->selectedGroups = array_values(array_intersect($this->selectedGroups, $visibleKeys));
        $this->clearToast();
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['search','fromDate','toDate'], true)) {
            $this->applyFilters();
        }
    }

    public function toggleSelectAll()
    {
        $allVisible = collect($this->groups)->pluck('group_key')->map(fn($v)=>(string)$v)->all();
        if (count($this->selectedGroups) === count($allVisible)) {
            $this->selectedGroups = array_values(array_diff($this->selectedGroups, $allVisible));
        } else {
            $this->selectedGroups = array_values(array_unique(array_merge($this->selectedGroups, $allVisible)));
        }
        $this->clearToast();
    }

    public function showDetails($index) { $this->activeRow = $this->groups[$index] ?? null; }
    public function closeModal() { $this->activeRow = null; }

    public function resetFilters(): void
    {
        $this->search   = '';
        $this->fromDate = '';
        $this->toDate   = '';
        $this->applyFilters();
        $this->clearToast();
    }

    /* ======= أدوات مساعدة للفواتير ======= */

    private function salesForGroup(string $groupKey)
    {
        return $this->provider->sales()
            ->where(function($q) use ($groupKey){
                $q->where('sale_group_id', $groupKey)
                  ->orWhere(function($qq) use ($groupKey){
                      $qq->whereNull('sale_group_id')->where('id', $groupKey);
                  });
            })
            ->get();
    }

    private function latestInvoiceIdForGroup(string $groupKey): ?int
    {
        $prefixes = ['PINV-G','PCN-G'];
        return Invoice::where(function($q) use ($groupKey,$prefixes){
                foreach ($prefixes as $p) $q->orWhere('invoice_number', $p.$groupKey);
            })
            ->latest('id')->value('id');
    }

    private function recalcInvoiceTotals(float $base): void
    {
        $this->isCreditNote = $base < 0;
        $b = $base;
        $tax = $this->taxIsPercent
            ? round($b * ($this->taxAmount/100), 2)
            : ($this->isCreditNote ? -abs($this->taxAmount) : abs($this->taxAmount));
        $this->invoiceTotals = ['base'=>$b,'tax'=>$tax,'net'=>$b+$tax];
    }

    private function groupNet(string $groupKey): float
    {
        $row = collect($this->groups)->firstWhere('group_key', $groupKey);
        return (float)($row->net_cost ?? 0.0);
    }

    /* ======= فاتورة فردية للمجموعة ======= */

    public function openInvoiceModal(string $groupKey)
    {
        $this->currentGroupKey = $groupKey;
        $this->taxAmount   = 0.0;
        $this->taxIsPercent= true;

        $base = $this->groupNet($groupKey);
        $this->recalcInvoiceTotals($base);

        $this->currentInvoiceId = $this->latestInvoiceIdForGroup($groupKey);
        $this->showInvoiceModal = true;
    }

    public function addTaxForGroup(): void
    {
        if (!$this->currentGroupKey) return;

        $base = $this->groupNet($this->currentGroupKey);
        $this->recalcInvoiceTotals($base);

        $user   = auth()->user();
        $agency = $user->agency;

        $prefix = $this->isCreditNote ? 'PCN-G' : 'PINV-G';
        $invoice = Invoice::updateOrCreate(
            ['invoice_number' => $prefix . $this->currentGroupKey],
            [
                'date'        => now()->toDateString(),
                'user_id'     => $user->id,
                'agency_id'   => $agency->id,
                'entity_name' => $this->provider->name,
                'subtotal'    => $this->invoiceTotals['base'],
                'tax_total'   => $this->invoiceTotals['tax'],
                'grand_total' => $this->invoiceTotals['net'],
            ]
        );

        // إرفاق سطور المبيعات الخاصة بهذه المجموعة مع توزيع الضريبة
        $sales = $this->salesForGroup($this->currentGroupKey);
        $subtotal = $sales->sum(function($s){
            $st = mb_strtolower(trim($s->status ?? ''));
            $isRefund = str_contains($st,'refund') || in_array($st,['void','cancel','canceled','cancelled'],true);
            return $isRefund ? -abs($s->usd_buy) : (float)$s->usd_buy;
        });

        $attach = [];
        $sumTax = 0.0; $i=0; $n=$sales->count();
        foreach ($sales as $s) {
            $i++;
            $st = mb_strtolower(trim($s->status ?? ''));
            $isRefund = str_contains($st,'refund') || in_array($st,['void','cancel','canceled','cancelled'],true);
            $baseLine = $isRefund ? -abs($s->usd_buy) : (float)$s->usd_buy;

            if ($this->taxIsPercent) {
                $lineTax = round($baseLine * ($this->taxAmount/100), 2);
            } else {
                $weight  = $subtotal != 0.0 ? ($baseLine / $subtotal) : 0.0;
                $lineTax = round($this->invoiceTotals['tax'] * $weight, 2);
            }
            if ($i === $n) $lineTax = round($this->invoiceTotals['tax'] - $sumTax, 2);
            $sumTax += $lineTax;

            $attach[$s->id] = [
                'base_amount'    => $baseLine,
                'tax_is_percent' => $this->taxIsPercent ? 1 : 0,
                'tax_input'      => $this->taxIsPercent ? (float)$this->taxAmount : $lineTax,
                'tax_amount'     => $lineTax,
                'line_total'     => $baseLine + $lineTax,
            ];
        }
        if ($attach) $invoice->sales()->syncWithoutDetaching($attach);

        $this->currentInvoiceId = $invoice->id;
    }

    /** تقبل null وتستخدم الخاصية كبديل لتفادي الخطأ */
    public function downloadSingleInvoicePdf(?int $invoiceId = null)
    {
        $invoiceId = $invoiceId ?: $this->currentInvoiceId;
        abort_if(!$invoiceId, 404, 'Invoice not found for this group');

        $invoice = Invoice::with(['sales','agency','user'])->findOrFail($invoiceId);

        // إعادة بناء كائن group للتصميم
        $sales = $invoice->sales;
        $beneficiary = optional($sales->first())->beneficiary_name ?? '—';
        $service     = optional(optional($sales->first())->service)->label ?? '-';

        // حسابات الأصل/الاسترداد والصافي للمزوّد من سطور الفاتورة
        $costTotalTrue = 0.0; $refundTotal = 0.0;
        foreach ($sales as $s) {
            $st = mb_strtolower(trim($s->status ?? ''));
            $isRefund = str_contains($st,'refund') || in_array($st,['void','cancel','canceled','cancelled'],true);
            $val = (float)($s->pivot->base_amount ?? $s->usd_buy);
            if ($isRefund) $refundTotal += abs($val);
            else $costTotalTrue += $val;
        }
        $netCost = $costTotalTrue - $refundTotal;

        $group = (object)[
            'group_key'        => $this->currentGroupKey ?? (string)($sales->first()->sale_group_id ?? $sales->first()->id ?? ''),
            'beneficiary_name' => $beneficiary,
            'sale_date'        => $invoice->date,
            'service_label'    => $service,
            'cost_total_true'  => $costTotalTrue,
            'refund_total'     => $refundTotal,
            'net_cost'         => $netCost,
            'scenarios'        => $sales->map(function($s){
                return [
                    'date'    => $s->sale_date,
                    'usd_buy' => (float)($s->pivot->base_amount ?? $s->usd_buy),
                    'status'  => $s->status,
                    'note'    => $s->reference ?? '-',
                ];
            })->values()->all(),
        ];

        $html = view('invoices.provider-invoice', [
            'invoice'  => $invoice,
            'provider' => $this->provider,
            'group'    => $group,
        ])->render();

        $pdfPath  = 'pdfs/provider-invoice-' . $invoice->id . '.pdf';
        $absolute = storage_path('app/public/' . $pdfPath);
        Storage::disk('public')->makeDirectory('pdfs');

        $chromePath = env('BROWSERSHOT_CHROME_PATH', PHP_OS_FAMILY === 'Windows'
            ? 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'
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

    /* ======= فاتورة مجمعة من المجموعات المحددة ======= */

    public function openBulkInvoiceModal()
    {
            if (empty($this->selectedGroups)) {
                $this->toastType = 'error';
                $this->toastMessage = 'اختر مجموعة واحدة على الأقل لإصدار فاتورة مجمّعة.';
                return;
            }

        $this->bulkTaxAmount    = 0.0;
        $this->bulkTaxIsPercent = true;

        $this->bulkSubtotal = collect($this->groups)
            ->whereIn('group_key', $this->selectedGroups)
            ->sum('net_cost');

        $this->invoiceEntityName = $this->provider->name;
        $this->invoiceDate = now()->toDateString();
        $this->showBulkInvoiceModal = true;
    }

    public function updatedSelectedGroups()
    {
        $this->clearToast();
    }

    public function createBulkInvoice()
    {
        $this->validate([
            'invoiceEntityName' => 'required|string|max:255',
            'invoiceDate'       => 'required|date',
        ]);

        if (empty($this->selectedGroups)) return;

        return DB::transaction(function () {
            $user   = auth()->user();
            $agency = $user->agency;

            $groups = collect($this->groups)->whereIn('group_key', $this->selectedGroups)->values();
            $subtotal = (float) $groups->sum('net_cost');

            $tax  = $this->bulkTaxIsPercent
                ? round($subtotal * ($this->bulkTaxAmount / 100), 2)
                : round((float)$this->bulkTaxAmount, 2);

            $grand = $subtotal + $tax;

            $invoice = Invoice::create([
                'invoice_number' => 'PINV-G-' . now()->format('YmdHis') . '-' . rand(100, 999),
                'entity_name'    => $this->invoiceEntityName,
                'date'           => $this->invoiceDate,
                'user_id'        => $user->id,
                'agency_id'      => $agency->id,
                'subtotal'       => $subtotal,
                'tax_total'      => $tax,
                'grand_total'    => $grand,
            ]);

            // إرفاق كل مبيعات المجموعات مع توزيع الضريبة
            $allSales = collect();
            foreach ($groups as $g) {
                $allSales = $allSales->merge($this->salesForGroup((string)$g->group_key));
            }

            $sumSoFar = 0.0; $i=0; $n=$allSales->count();
            foreach ($allSales as $s) {
                $i++;
                $st = mb_strtolower(trim($s->status ?? ''));
                $isRefund = str_contains($st,'refund') || in_array($st,['void','cancel','canceled','cancelled'],true);
                $baseLine = $isRefund ? -abs($s->usd_buy) : (float)$s->usd_buy;

                if ($this->bulkTaxIsPercent) {
                    $lineTax = round($baseLine * ($this->bulkTaxAmount/100), 2);
                } else {
                    $weight  = $subtotal != 0.0 ? ($baseLine / $subtotal) : 0.0;
                    $lineTax = round($tax * $weight, 2);
                }
                if ($i === $n) $lineTax = round($tax - $sumSoFar, 2);
                $sumSoFar += $lineTax;

                $invoice->sales()->syncWithoutDetaching([
                    $s->id => [
                        'base_amount'    => $baseLine,
                        'tax_is_percent' => $this->bulkTaxIsPercent ? 1 : 0,
                        'tax_input'      => $this->bulkTaxIsPercent ? (float)$this->bulkTaxAmount : $lineTax,
                        'tax_amount'     => $lineTax,
                        'line_total'     => $baseLine + $lineTax,
                    ],
                ]);
            }

            $this->showBulkInvoiceModal = false;
            $this->selectedGroups = [];

            return $this->downloadBulkInvoicePdf($invoice->id);
        });
    }

    public function downloadBulkInvoicePdf($invoiceId)
    {
        $invoice = Invoice::with(['sales','agency','user'])->findOrFail($invoiceId);

        $html = view('invoices.provider-bulk-invoice', [
            'invoice'  => $invoice,
            'provider' => $this->provider,
        ])->render();

        $pdfPath  = 'pdfs/provider-bulk-invoice-' . $invoice->id . '.pdf';
        $absolute = storage_path('app/public/' . $pdfPath);
        Storage::disk('public')->makeDirectory('pdfs');

        $chromePath = env('BROWSERSHOT_CHROME_PATH', PHP_OS_FAMILY === 'Windows'
            ? 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'
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

    public function exportSelected()
    {
        if (empty($this->selectedGroups)) return;
        $ids = implode(',', array_map('strval', $this->selectedGroups));
        return redirect()->route('agency.provider-invoices.export', ['provider' => $this->provider->id, 'ids' => $ids]);
    }

    public function render()
    {
        return view('livewire.agency.provider-invoice-overview', [
            'provider' => $this->provider,
            'groups'   => $this->groups,
        ])->layout('layouts.agency');
    }
}
