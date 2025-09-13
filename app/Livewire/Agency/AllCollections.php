<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\WalletTransaction;

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
       $totalPaid   = $sale->total_paid ?? ($sale->collections->sum('amount') + ($sale->amount_paid ?? 0));
       $totalAmount = $sale->usd_sell ?? ($sale->invoice_total_true ?? 0); // الصافي بعد الاستردادات


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

            // مفاتيح للتحصيل لتجنب ازدواجية مع سحب المحفظة
            $minuteKey = fn($dt) => \Carbon\Carbon::parse($dt)->format('Y-m-d H:i');
            $moneyKey  = fn($n)  => number_format((float)$n, 2, '.', '');
            $collectionKeys = [];
            foreach ($collections as $c) {
                $k = $minuteKey($c->created_at ?? $c->payment_date) . '|' . $moneyKey($c->amount);
                $collectionKeys[$k] = ($collectionKeys[$k] ?? 0) + 1;
            }

            // سحب المحفظة المرتبط بالمجموعة فقط
            $groupId  = $sales->first()->sale_group_id ?? $sales->first()->id;
            $walletPaid = WalletTransaction::whereHas('wallet', fn($q)=>$q->where('customer_id', $first->customer_id))
                ->where('type', 'withdraw')
                ->when($this->startDate, fn($q)=>$q->where('created_at','>=',$this->startDate))
                ->when($this->endDate,   fn($q)=>$q->where('created_at','<=',$this->endDate))
                ->get()
                ->reduce(function($sum,$t) use($groupId,$minuteKey,$moneyKey,$collectionKeys){
                    $ref = strtolower((string)($t->reference ?? ''));
                    if (str_starts_with($ref, 'sales-auto|group:') && str_contains($ref, (string)$groupId)) {
                        return $sum + (float)$t->amount; // سداد من المحفظة لهذه المجموعة
                    }
                    // تجاهل السحب المطابق لتحصيل بنفس الدقيقة والمبلغ
                    $k = $minuteKey($t->created_at).'|'.$moneyKey($t->amount);
                    if (!empty($collectionKeys[$k])) return $sum;
                    return $sum;
                }, 0.0);

            $totalPaid = $amountPaid + $collectionsSum + $walletPaid;


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
                'usd_sell'               => $netTotal,      // الصافي بعد الاستردادات
                'has_refund'             => $hasRefund,
                'refund_total'           => $refundTotal,
                'invoice_total_true'     => $invoiceTotalTrue,
                'invoice_total_display'  => $invoiceTotalTrue,
                'amount_paid'            => $amountPaid,    // سداد مباشر مع الفاتورة
                'wallet_paid'            => $walletPaid,    // سداد بالمحفظة للمجموعة
                'collections'            => $collections,   // تحصيلات يدوية/نقدية
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
            'total'            => $sale->usd_sell ?? ($sale->invoice_total_true ?? 0),
            'collected'        => (float) ($sale->total_paid ?? $sale->collections->sum('amount')),
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
