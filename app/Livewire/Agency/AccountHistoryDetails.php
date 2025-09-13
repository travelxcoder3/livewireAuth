<?php

namespace App\Livewire\Agency;

use App\Models\Customer;
use App\Models\WalletTransaction;
use Livewire\Component;


class AccountHistoryDetails extends Component
{
    public Customer $customer;
    public $collections;
    public $activeSale = null;

    public function mount(Customer $customer)
    {
        $this->customer = $customer;

      $sales   = $customer->sales()->with(['collections','service'])->get();
        $grouped = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

        // مفاتيح مطابقة دقيقة+مبلغ
        $minuteKey = fn($dt) => \Carbon\Carbon::parse($dt)->format('Y-m-d H:i');
        $moneyKey  = fn($n)  => number_format((float)$n, 2, '.', '');

        // معاملات المحفظة لكل عميل
        $walletTx = WalletTransaction::whereHas('wallet', fn($q)=>$q->where('customer_id', $customer->id))->get();

        $this->collections = $grouped->map(function ($sales) use ($walletTx, $minuteKey, $moneyKey) {

    $s = $sales->sortByDesc('created_at')->first();

    $refundStatuses = ['refund-full','refund_partial','refund-partial','refunded'];

    // إجمالي الفاتورة (الأصل) = جميع السطور غير الاستردادات وبقيمة موجبة
    $invoiceTotalTrue = $sales->reject(function ($x) use ($refundStatuses) {
            return in_array(strtolower($x->status ?? ''), $refundStatuses);
        })
        ->sum('usd_sell');

    // إجمالي الاستردادات كموجب
    $refundTotal = $sales->filter(function ($x) use ($refundStatuses) {
            return in_array(strtolower($x->status ?? ''), $refundStatuses);
        })
        ->sum(fn($x) => abs($x->usd_sell));

    // الصافي بعد الاستردادات
    $netTotal = $invoiceTotalTrue - $refundTotal;

    // التحصيلات والمدفوعات
   // التحصيلات + السداد المباشر + السداد بالمحفظة
$groupId = $sales->first()->sale_group_id ?? $sales->first()->id;

// مفاتيح التحصيل لتفادي العدّ المزدوج مع سحب المحفظة
$collectionSum  = 0.0;
$collectionKeys = [];
foreach ($sales as $sx) {
    foreach ($sx->collections as $c) {
        $collectionSum += (float)$c->amount;
        $k = $minuteKey($c->created_at ?? $c->payment_date).'|'.$moneyKey($c->amount);
        $collectionKeys[$k] = ($collectionKeys[$k] ?? 0) + 1;
    }
}

// سحب المحفظة الخاص بهذه المجموعة
$walletWithdrawForGroup = 0.0;
foreach ($walletTx as $t) {
    if (($t->type ?? '') !== 'withdraw') continue;
    $ref = strtolower((string)($t->reference ?? ''));

    // سداد آلي مربوط بالمجموعة
    if (str_starts_with($ref, 'sales-auto|group:') && str_contains($ref, (string)$groupId)) {
        $walletWithdrawForGroup += (float)$t->amount;
        continue;
    }

    // تجاهل السحب المطابق لتحصيل بنفس الدقيقة والمبلغ
    $k = $minuteKey($t->created_at).'|'.$moneyKey($t->amount);
    if (!empty($collectionKeys[$k])) continue;
}

$paid      = (float)$sales->sum('amount_paid');
$totalPaid = $collectionSum + $paid + $walletWithdrawForGroup;


    // المتبقي/رصيد للعميل
    $remainingForCustomer = max(0, $netTotal - $totalPaid);
    $remainingForCompany  = max(0, $totalPaid - $netTotal);

    return (object) [
        // للعرض
        'beneficiary_name' => $s->beneficiary_name ?? optional($s->customer)->name ?? '—',
        'sale_date'        => $s->sale_date,
        'service_label'    => $s->service->label ?? '-',

        // مثل الكارد:
        'invoice_total_true' => $invoiceTotalTrue,  // إجمالي الفاتورة (الأصل)
        'refund_total'       => $refundTotal,       // إجمالي الاستردادات
        'net_total'          => $netTotal,          // الصافي بعد الاستردادات
        'total_collected'    => $totalPaid,          // إجمالي المدفوع (تحصيلات + amount_paid)

        'remaining_for_customer' => $remainingForCustomer,
        'remaining_for_company'  => $remainingForCompany,

        // للحفظ/التوافق إن كان فيه استخدامات قديمة:
        'amount_paid' => $paid,
         'collected'   => $collectionSum, // مجموع التحصيلات فقط
        'total_paid'  => $totalPaid,
        'usd_sell'    => $netTotal,                 // كان يُستخدم سابقًا كصافي

        'note' => $s->note,

        'scenarios' => $sales->map(function ($sale) {
            return [
                'date'        => $sale->sale_date,
                'usd_sell'    => $sale->usd_sell,
                'amount_paid' => $sale->amount_paid,
                'status'      => $sale->status,
                'note'        => $sale->reference ?? '-',
            ];
        }),

        'collections' => $sales->flatMap->collections->map(function ($col) {
            return [
                'amount'       => $col->amount,
                'payment_date' => $col->payment_date,
                'note'         => $col->note,
            ];
        }),
    ];
})->values();

    }


    public function render()
    {
        return view('livewire.agency.account-history-details', [
            'customer' => $this->customer, // ✅ هذا هو المهم
            'collections' => $this->collections, // اختياري إذا كنت تريد تمريره
        ])->layout('layouts.agency');
    }

    public function showDetails($index)
    {
        $this->activeSale = $this->collections[$index];
    }

    public function closeModal()
    {
        $this->activeSale = null;
    }


}
