<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;

class AllCollections extends Component
{
    use WithPagination;

    public $search = '';
    public $sales = [];
    public $activeSaleId = null;

    // فلاتر التاريخ
    public ?string $startDate = null;
    public ?string $endDate   = null;
    public int $filtersVersion = 0;


    public function mount()
    {
        $this->loadSales();
    }

    public function getPaymentStatus($sale)
    {
        $totalPaid   = $sale->collections->sum('amount') + ($sale->amount_paid ?? 0);
        $totalAmount = $sale->invoice_total_true ?? $sale->usd_sell;

        if ($totalPaid == 0) {
            return ['status' => 'لم يبدأ التحصيل', 'color' => 'bg-gray-100 text-gray-800'];
        } elseif ($totalPaid < $totalAmount) {
            return ['status' => 'تحصيل جزئي', 'color' => 'bg-amber-100 text-amber-800'];
        } else {
            return ['status' => 'تم التحصيل بالكامل', 'color' => 'bg-green-100 text-green-800'];
        }
    }

    public function showCollectionDetails($saleId) { $this->activeSaleId = $saleId; }
    public function closeModal() { $this->activeSaleId = null; }

    public function render()
    {
        return view('livewire.agency.all-collections')->layout('layouts.agency');
    }

    public function loadSales()
    {
        // ضبط المدى إن كان معكوسًا
        if ($this->startDate && $this->endDate && $this->startDate > $this->endDate) {
            [$this->startDate, $this->endDate] = [$this->endDate, $this->startDate];
        }

        $rawSales = Sale::with(['collections','customer'])
            ->where('agency_id', auth()->user()->agency_id)
            // بحث عام
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('beneficiary_name', 'like', '%'.$this->search.'%')
                       ->orWhere('usd_sell', 'like', '%'.$this->search.'%')
                       ->orWhere('id', 'like', '%'.$this->search.'%');
                });
            })
            // فلترة بالتاريخ على sale_date
           // فلترة بالتاريخ على sale_date
            ->when($this->startDate, fn($q) => $q->whereDate('sale_date', '>=', $this->startDate))
            ->when($this->endDate,   fn($q) => $q->whereDate('sale_date', '<=', $this->endDate))
            ->get();


        $grouped = $rawSales->groupBy(fn ($item) => $item->sale_group_id ?? $item->id);

        $this->sales = $grouped->map(function ($sales) {
            $first = $sales->sortByDesc('created_at')->first();

            $refundStatuses = ['refund-full','refund_partial','refund-partial','refunded','void','cancel','canceled','cancelled'];

            $amountPaid     = $sales->sum('amount_paid');
            $collections    = $sales->flatMap->collections;
            $collectionsSum = $collections->sum('amount');
            $totalPaid      = $amountPaid + $collectionsSum;

            $hasRefund = $sales->contains(fn($s) => in_array(strtolower($s->status ?? ''), $refundStatuses));

            $invoiceTotalTrue = $sales->filter(function ($s) use ($refundStatuses) {
                    $st = strtolower($s->status ?? '');
                    return !in_array($st, $refundStatuses) && ($s->usd_sell ?? 0) > 0;
                })
                ->sum(fn($s) => $s->usd_sell);

            $refundTotal = $sales->filter(fn($s) => in_array(strtolower($s->status ?? ''), $refundStatuses))
                                 ->sum(fn($s) => abs($s->usd_sell));

            $netTotal  = $invoiceTotalTrue - $refundTotal;
            $remaining = $netTotal - $totalPaid;

            return (object)[
                'id'                     => $first->id,
                'group_id'               => $first->sale_group_id,
                'beneficiary_name'       => $first->beneficiary_name ?? optional($first->customer)->name ?? '—',
                'usd_sell'               => $netTotal,
                'has_refund'             => $hasRefund,
                'refund_total'           => $refundTotal,
                'invoice_total_true'     => $invoiceTotalTrue,
                'invoice_total_display'  => $invoiceTotalTrue,
                'amount_paid'            => $amountPaid,
                'collections'            => $collections,
                'total_paid'             => $totalPaid,
                'remaining_for_customer' => $remaining > 0 ? $remaining : 0,
                'remaining_for_company'  => $remaining < 0 ? abs($remaining) : 0,
                'referred_by_customer'   => optional($first->customer)->name ?? 'لا يوجد عميل',
                'created_at'             => $first->created_at,
                'scenarios'              => $sales->map(function ($sale) {
                    return [
                        'date'        => $sale->sale_date,
                        'usd_sell'    => $sale->usd_sell,
                        'amount_paid' => $sale->amount_paid,
                        'status'      => $sale->status,
                        'note'        => $sale->reference ?? '-',
                    ];
                }),
            ];
        })->sortByDesc('created_at')->values();
    }
    public function updatedSearch($value)
{
    $this->search = trim($value);
    $this->resetPage();
    $this->loadSales();
}
public function updatedStartDate()  { $this->resetPage(); $this->loadSales(); }
public function updatedEndDate()    { $this->resetPage(); $this->loadSales(); }
public function clearDateFilters()
{
    $this->search    = '';
    $this->startDate = null;
    $this->endDate   = null;

    $this->filtersVersion++;   // يجبر إعادة رسم الحقول
    $this->resetPage();
    $this->loadSales();
}

public function getRowsProperty()
{
    $map = function ($sale) {
        $payment = $this->getPaymentStatus($sale);

        return (object)[
            'id'               => $sale->id,
            'beneficiary_name' => $sale->beneficiary_name,
            'status_html'      => $payment['status'], // نص فقط
            'total'            => $sale->invoice_total_true ?? $sale->usd_sell,
            'collected'        => (float) $sale->collections->sum('amount'),
            'count'            => (int) $sale->collections->count(),
            'created_human'    => $sale->created_at?->diffForHumans(),
        ];
    };

    if (is_object($this->sales) && method_exists($this->sales, 'through')) {
        return $this->sales->through($map);
    }

    return collect($this->sales)->map($map);
}


}
