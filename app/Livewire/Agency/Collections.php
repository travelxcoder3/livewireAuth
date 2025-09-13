<?php

namespace App\Livewire\Agency;

use App\Models\Sale;
use App\Models\DynamicListItemSub;
use App\Models\Collection;
use App\Models\WalletTransaction;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Collections extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;
    public $customerType = '';
    public $debtType = '';
    public $responseType = '';
    public $relationType = '';
    public $movementType = '';

 public function render()
{
    // ✅ جلب كل المبيعات المرتبطة بالعملاء مع علاقاتها
    $allSales = Sale::with([
        'customer',
        'collections' => function ($q) {
            $q->latest();
        },
        'collections.customerType',
        'collections.debtType',
        'collections.customerResponse',
        'collections.customerRelation'
    ])
        ->where('agency_id', Auth::user()->agency_id)
    
        ->when($this->search, fn($q) =>
            $q->whereHas('customer', fn($q2) =>
                $q2->where('name', 'like', "%{$this->search}%")
            )
        )
        ->when($this->startDate, fn($q) =>
            $q->whereDate('sale_date', '>=', $this->startDate)
        )
        ->when($this->endDate, fn($q) =>
            $q->whereDate('sale_date', '<=', $this->endDate)
        )
        ->get();

// ✅ توحيد المنطق: الرصيد الصافي = (له − عليه) من المحفظة + المبيعات + التحصيلات + الاستردادات
$groupedByCustomer = $allSales->groupBy('customer_id');

$customers = $groupedByCustomer->map(function ($sales) {
    $firstSale = $sales->first();
    if (!$firstSale || !$firstSale->customer) {
        return null;
    }

    $customer = $firstSale->customer;

    // الصافي: موجب = للشركة للعميل، سالب = على العميل للشركة
    $net = $this->netForCustomer((int)$customer->id);

    // تفكيك الصافي إلى عمودين متوافقين مع الشاشات الأخرى
    $remainingForCustomer = $net < 0 ? abs($net) : 0.0; // عليه
    $remainingForCompany  = $net > 0 ? $net      : 0.0; // له

    // تجاهل الصفوف الصفرية
    if ($remainingForCustomer == 0 && $remainingForCompany == 0) {
        return null;
    }

    $latestCollection = $sales->flatMap->collections->sortByDesc('payment_date')->first();

    return (object) [
        'id'   => $customer->id,
        'name' => $customer->name,

        'remaining_for_customer' => $remainingForCustomer,
        'remaining_for_company'  => $remainingForCompany,
        'net_due'                => $remainingForCustomer - $remainingForCompany, // = -$net
        'debt_amount' => round($net, 2),
        'last_payment'     => optional($latestCollection)->payment_date,
        'customer_type'    => optional($latestCollection?->customerType)->label ?? '-',
        'debt_type'        => optional($latestCollection?->debtType)->label ?? '-',
        'customer_response'=> optional($latestCollection?->customerResponse)->label ?? '-',
        'customer_relation'=> optional($latestCollection?->customerRelation)->label ?? '-',
        'first_sale_id'    => $sales->first()->id,
    ];
})->filter()->values();


    return view('livewire.agency.collections', [
        'sales' => $customers,
        'customerTypes' => $this->getOptions('نوع العميل'),
        'debtTypes' => $this->getOptions('نوع المديونية'),
        'responseTypes' => $this->getOptions('تجاوب العميل'),
        'relationTypes' => $this->getOptions('نوع ارتباطه بالشركة'),
    ])->layout('layouts.agency');
}



    protected function getOptions($label)
    {
        return DynamicListItemSub::whereHas('parentItem', fn($q) =>
            $q->where('label', $label)
        )->get();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->customerType = '';
        $this->debtType = '';
        $this->responseType = '';
        $this->relationType = '';
        $this->movementType = '';
    }

    private function netForCustomer(int $customerId): float
{
    $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
    $void   = ['void','cancel','canceled','cancelled'];

    $sumD = 0.0; // عليه
    $sumC = 0.0; // له

    // معاملات المحفظة للعميل
    $walletTx = WalletTransaction::whereHas('wallet', fn($q)=>$q->where('customer_id', $customerId))
        ->orderBy('created_at')->get();

    // مُطابقة سحب المحفظة مع التحصيلات لتجنب الازدواج
    $walletWithdrawAvail = [];
    foreach ($walletTx as $t) {
        if (strtolower((string)$t->type) === 'withdraw') {
            $k = $this->minuteKey($t->created_at) . '|' . $this->moneyKey($t->amount);
            $walletWithdrawAvail[$k] = ($walletWithdrawAvail[$k] ?? 0) + 1;
        }
    }

    // المبيعات + الاستردادات + amount_paid
    $refundCreditKeys = [];
    $sales = Sale::where('customer_id', $customerId)->orderBy('created_at')->get();

    foreach ($sales as $s) {
        $st = mb_strtolower(trim((string)$s->status));

        if (!in_array($st, $refund, true) && !in_array($st, $void, true)) {
            $sumD += (float)($s->invoice_total_true ?? $s->usd_sell ?? 0);
        }

        if (in_array($st, $refund, true) || in_array($st, $void, true)) {
            $amt = (float)($s->refund_amount ?? 0);
            if ($amt <= 0) { $amt = abs((float)($s->usd_sell ?? 0)); }
            $sumC += $amt;

            $keyR = $this->minuteKey($s->created_at) . '|' . $this->moneyKey($amt);
            $refundCreditKeys[$keyR] = ($refundCreditKeys[$keyR] ?? 0) + 1;
        }

        if ((float)$s->amount_paid > 0) {
            $sumC += (float)$s->amount_paid;
        }
    }

    // التحصيلات: استبعد ما قابله سحب محفظة أو استرداد
    $collections = Collection::with('sale')
        ->whereHas('sale', fn($q)=>$q->where('customer_id',$customerId))
        ->orderBy('created_at')->get();

    $collectionKeys = [];
    foreach ($collections as $c) {
        $evt = $this->minuteKey($c->created_at ?? $c->payment_date);
        $k   = $evt . '|' . $this->moneyKey($c->amount);
        $collectionKeys[$k] = ($collectionKeys[$k] ?? 0) + 1;
    }

    foreach ($collections as $c) {
        $evt = $this->minuteKey($c->created_at ?? $c->payment_date);
        $k   = $evt . '|' . $this->moneyKey($c->amount);

        if (($refundCreditKeys[$k] ?? 0) > 0) { $refundCreditKeys[$k]--; continue; }
        if (($walletWithdrawAvail[$k] ?? 0) > 0) { $walletWithdrawAvail[$k]--; continue; }

        $sumC += (float)$c->amount;
    }

    // معاملات المحفظة: تجاهل إيداعات استرداد sales-auto
    foreach ($walletTx as $tx) {
        $evt  = $this->minuteKey($tx->created_at);
        $k    = $evt . '|' . $this->moneyKey($tx->amount);
        $type = strtolower((string)$tx->type);
        $ref  = Str::lower((string)$tx->reference);

        if ($type === 'deposit') {
            if (Str::contains($ref, 'sales-auto|group:')) continue;     // إيداع ناتج عن استرداد
            if (($refundCreditKeys[$k] ?? 0) > 0) { $refundCreditKeys[$k]--; continue; }
            $sumC += (float)$tx->amount;
        } elseif ($type === 'withdraw') {
            if (($collectionKeys[$k] ?? 0) > 0) { $collectionKeys[$k]--; continue; }
            $sumD += (float)$tx->amount;
        }
    }

    // الصافي: موجب = للشركة للعميل، سالب = على العميل للشركة
    return round($sumC - $sumD, 2);
}

private function minuteKey($dt): string {
    try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i'); }
    catch (\Throwable $e) { return (string)$dt; }
}

private function moneyKey($n): string {
    return number_format((float)$n, 2, '.', '');
}

}
