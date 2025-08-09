<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;

class AllCollections extends Component
{
    use WithPagination;

    public $search = '';
    public $sales = []; // تغيير الاسم ليكون أكثر شمولاً
    public $activeSaleId = null;

    public function mount()
    {
        $this->loadSales();
    }

  

public function getPaymentStatus($sale)
{
    $totalPaid   = $sale->collections->sum('amount') + ($sale->amount_paid ?? 0);
    $totalAmount = $sale->invoice_total_true ?? $sale->usd_sell; // ← الحقيقي دائماً

    if ($totalPaid == 0) {
        return ['status' => 'لم يبدأ التحصيل', 'color' => 'bg-gray-100 text-gray-800'];
    } elseif ($totalPaid < $totalAmount) {
        return ['status' => 'تحصيل جزئي', 'color' => 'bg-amber-100 text-amber-800'];
    } else {
        return ['status' => 'تم التحصيل بالكامل', 'color' => 'bg-green-100 text-green-800'];
    }
}



public function showCollectionDetails($saleId)
{
    $this->activeSaleId = $saleId;
}


    public function closeModal()
    {
        $this->activeSaleId = null;
    }

    public function render()
    {
        return view('livewire.agency.all-collections')
            ->layout('layouts.agency');
    }

   

public function loadSales()
{
    $rawSales = Sale::with(['collections','customer'])
        ->where('agency_id', auth()->user()->agency_id)
        ->when($this->search, function ($q) {
            $q->where('beneficiary_name', 'like', '%'.$this->search.'%')
              ->orWhere('usd_sell', 'like', '%'.$this->search.'%')
              ->orWhere('id', 'like', '%'.$this->search.'%');
        })
        ->get();

    // ✅ التجميع حسب sale_group_id أو id
    $grouped = $rawSales->groupBy(function ($item) {
        return $item->sale_group_id ?? $item->id;
    });

    // ✅ تجهيز بيانات العرض
$this->sales = $grouped->map(function ($sales) {
    $first = $sales->sortByDesc('created_at')->first(); // نأخذ الأحدث

$refundStatuses = ['refund-full','refund_partial','refund-partial','refunded'];

$amountPaid     = $sales->sum('amount_paid');
$collections    = $sales->flatMap->collections;
$collectionsSum = $collections->sum('amount');
$totalPaid      = $amountPaid + $collectionsSum;

$hasRefund = $sales->contains(function ($s) use ($refundStatuses) {
    return in_array(strtolower($s->status ?? ''), $refundStatuses);
});

// إجمالي الفاتورة الحقيقي (القيم الإيجابية فقط: Issued/Re-Issued/... بدون Refund)
$invoiceTotalTrue = $sales->filter(function ($s) use ($refundStatuses) {
        $st = strtolower($s->status ?? '');
        return !in_array($st, $refundStatuses) && ($s->usd_sell ?? 0) > 0;
    })
    ->sum(fn($s) => $s->usd_sell);

// إجمالي الاستردادات (قيمة موجبة)
$refundTotal = $sales->filter(function ($s) use ($refundStatuses) {
        return in_array(strtolower($s->status ?? ''), $refundStatuses);
    })
    ->sum(fn($s) => abs($s->usd_sell));

// الصافي المحاسبي = الأصل − الاستردادات
$netTotal  = $invoiceTotalTrue - $refundTotal;
$remaining = $netTotal - $totalPaid;

return (object)[
        'id'                   => $first->id,
    'group_id'             => $first->sale_group_id,
    'beneficiary_name'     => $first->beneficiary_name ?? optional($first->customer)->name ?? '—',
    // نُخزّن الصافي هنا ليستمر باقي الحسابات كما هي
    'usd_sell'             => $netTotal,

    // قيم العرض/التقارير
    'has_refund'           => $hasRefund,
    'refund_total'         => $refundTotal,
    'invoice_total_true'   => $invoiceTotalTrue, // ← 1100
    'invoice_total_display'=> $invoiceTotalTrue, // نعرض الأصل هنا في هذا التقرير

    'amount_paid'          => $amountPaid,
    'collections'          => $collections,
    'total_paid'           => $totalPaid,
    'remaining_for_customer'=> $remaining > 0 ? $remaining : 0,
    'remaining_for_company' => $remaining < 0 ? abs($remaining) : 0,    'referred_by_customer' => optional($first->customer)->name ?? 'لا يوجد عميل',
    'created_at' => $first->created_at,
    'scenarios' => $sales->map(function ($sale) {
        return [
            'date' => $sale->sale_date,
            'usd_sell' => $sale->usd_sell,
            'amount_paid' => $sale->amount_paid,
            'status' => $sale->status,
            'note' => $sale->reference ?? '-',
        ];
    }),
];


})

->sortByDesc('created_at') // ← ترتيب نهائي للمخرجات
->values();

}



    public function updatedSearch()
    {
        $this->loadSales();
    }

    // ... باقي الدوال الموجودة سابقاً ...
}