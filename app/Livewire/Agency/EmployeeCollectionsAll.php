<?php

namespace App\Livewire\Agency;

use App\Models\Sale;
use App\Models\DynamicListItemSub;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Collection;
use Illuminate\Support\Str;
class EmployeeCollectionsAll extends Component
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
    // اجلب مبيعات الوكالة مع علاقات التحصيلات
    $allSales = Sale::with([
            'customer',
            'collections' => fn($q) => $q->latest(),
            'collections.customerType',
            'collections.debtType',
            'collections.customerResponse',
            'collections.customerRelation',
        ])
        ->where('agency_id', Auth::user()->agency_id)
        ->when($this->search, fn($q) =>
            $q->whereHas('customer', fn($qq) =>
                $qq->where('name', 'like', "%{$this->search}%")
            )
        )
        ->when($this->startDate, fn($q) =>
            $q->whereDate('sale_date', '>=', $this->startDate)
        )
        ->when($this->endDate, fn($q) =>
            $q->whereDate('sale_date', '<=', $this->endDate)
        )
        ->get();

    // تجميع حسب العميل وحساب الصافي من المحفظة + المبيعات + التحصيلات + الاستردادات مع إزالة الازدواجية
    $rows = $allSales->groupBy('customer_id')->map(function ($sales, $customerId) {
        $first = $sales->first();
        if (!$first || !$first->customer) return null;

        $customer = $first->customer;

        // الصافي: له − عليه
        $net = $this->netForCustomer((int)$customerId);

        // قسّم الصافي إلى جانبين
        $remainingForCustomer = $net < 0 ? abs($net) : 0.0; // عليه
        $remainingForCompany  = $net > 0 ? $net        : 0.0; // له

        // تجاهل الأصفار
        if ($remainingForCustomer == 0.0 && $remainingForCompany == 0.0) return null;

        $latestCollection = $sales->flatMap->collections->sortByDesc('payment_date')->first();

        return (object)[
            'id'                      => $customer->id,
            'name'                    => $customer->name,
            'remaining_for_customer'  => round($remainingForCustomer, 2),
            'remaining_for_company'   => round($remainingForCompany, 2),
            'net_due'                 => round($net, 2), // موجب = للشركة عليه، سالب = عليه للشركة
            'last_payment'            => optional($latestCollection)->payment_date,
            'customer_type'           => optional($latestCollection?->customerType)->label ?? '-',
            'debt_type'               => optional($latestCollection?->debtType)->label ?? '-',
            'customer_response'       => optional($latestCollection?->customerResponse)->label ?? '-',
            'customer_relation'       => optional($latestCollection?->customerRelation)->label ?? '-',
            'first_sale_id'           => $sales->first()->id,
        ];
    })->filter()->values();

    return view('livewire.agency.employee-collections-all', [
        'sales'          => $rows,
        'customerTypes'  => $this->getOptions('نوع العميل'),
        'debtTypes'      => $this->getOptions('نوع المديونية'),
        'responseTypes'  => $this->getOptions('تجاوب العميل'),
        'relationTypes'  => $this->getOptions('نوع ارتباطه بالشركة'),
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

    // كل معاملات المحفظة
    $walletTx = WalletTransaction::whereHas('wallet', fn($q)=>$q->where('customer_id', $customerId))
        ->orderBy('created_at')->get();

    // مفاتيح سحوبات المحفظة لمعادلة التحصيلات
    $walletWithdrawAvail = [];
    foreach ($walletTx as $t) {
        if (strtolower((string)$t->type) === 'withdraw') {
            $k = $this->minuteKey($t->created_at) . '|' . $this->moneyKey($t->amount);
            $walletWithdrawAvail[$k] = ($walletWithdrawAvail[$k] ?? 0) + 1;
        }
    }

    // المبيعات + الاستردادات
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

    // التحصيلات مع إزالة الازدواجية مع السحوبات والاستردادات
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

    // معاملات المحفظة المتبقية
    foreach ($walletTx as $tx) {
        $evt  = $this->minuteKey($tx->created_at);
        $k    = $evt . '|' . $this->moneyKey($tx->amount);
        $type = strtolower((string)$tx->type);
        $ref  = Str::lower((string)$tx->reference);

        if ($type === 'deposit') {
            // تجاهل إيداعات استرداد المبيعات الآلية
            if (Str::contains($ref, 'sales-auto|group:')) continue;
            if (($refundCreditKeys[$k] ?? 0) > 0) { $refundCreditKeys[$k]--; continue; }
            $sumC += (float)$tx->amount;
        } elseif ($type === 'withdraw') {
            if (($collectionKeys[$k] ?? 0) > 0) { $collectionKeys[$k]--; continue; }
            $sumD += (float)$tx->amount;
        }
    }

    return round($sumC - $sumD, 2); // موجب = له، سالب = عليه
}

private function minuteKey($dt): string {
    try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i'); }
    catch (\Throwable $e) { return (string)$dt; }
}
private function moneyKey($n): string {
    return number_format((float)$n, 2, '.', '');
}

}
