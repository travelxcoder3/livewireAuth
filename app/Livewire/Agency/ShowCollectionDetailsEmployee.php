<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use App\Models\DynamicListItemSub;
use Livewire\WithPagination;
use App\Models\Collection;
use Illuminate\Support\Str;
use App\Models\WalletTransaction;

class ShowCollectionDetailsEmployee extends Component
{
    use WithPagination;
    public $sale;
    public string $accountTypeLabel = '-'; 

    private function mapAccountType(?string $t): string
    {
        return match($t){
            'individual'   => 'فرد',
            'company'      => 'شركة',
            'organization' => 'منظمة',
            default        => '-',
        };
    }
    public $services = [];
    public $saleId;
    public $totalAmount = 0;
    public $amountReceived = 0;
    public $remainingAmount = 0;
    public $showEditModal = false;
    public $paidAmount;
    public $paidFromSales = 0;
    public $paidFromCollections = 0;
    public $paidTotal = 0;
    public $payRemainingNow = 0;

    public $customerSales = [];
    public $availableBalanceToPayOthers = 0;
    public $payToCustomerList = [];
    public $selectedPayCustomerId = null;

    public function mount($sale)
    {
        $this->sale = Sale::with([
            'customer',
            'collections.customerType',
            'collections.debtType',
            'collections.customerResponse',
            'collections.customerRelation',
            'collections.user', 
        ])
        ->where('agency_id', Auth::user()->agency_id)
        ->findOrFail($sale);
        $this->accountTypeLabel = $this->mapAccountType($this->sale->customer->account_type ?? null); // جديد

        $this->calculateAmounts();

// ابنِ صفوف المجموعات (الإجمالي/المدفوع/التحصيلات) + المتبقي الخام
$this->customerSales = $this->buildCustomerSalesRows((int)$this->sale->customer_id);

// طبّق سياسة التوزيع: رصيد المحفظة/الصافي الموجب يذهب للأقل دينًا فالأكثر
$credit = max(0, $this->netForCustomer((int)$this->sale->customer_id));
$this->customerSales = $this->distributeCreditAsc($this->customerSales, $credit);

// استخدم نفس الرصيد كـ “رصيد متاح للسداد لغيره”
$this->availableBalanceToPayOthers = $credit;

// عدّل المتبقي للعملية الحالية ليطابق التوزيع
$currentKey = $this->groupKey($this->sale);
if ($row = $this->customerSales->first(fn($r) => (($r->group_id ?? $r->id) == $currentKey))) {
    $this->remainingAmount = round(max(0,(float)$row->remaining), 2);
}


}

    protected function calculateAmounts()
    {
        $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
        $void   = ['void','cancel','canceled','cancelled'];

        $idsQuery = Sale::with('collections')
            ->where('agency_id', Auth::user()->agency_id);

        if ($this->sale->sale_group_id) {
            $idsQuery->where('sale_group_id', $this->sale->sale_group_id);
        } else {
            $idsQuery->where('id', $this->sale->id);
        }

        $sales = $idsQuery->get();

        // الإجمالي يعتمد invoice_total_true عند توفره ويستثني الإلغاء/الاسترداد
        $this->totalAmount = $sales->filter(function($s) use($refund,$void){
                $st = mb_strtolower((string)$s->status);
                return !in_array($st,$refund,true) && !in_array($st,$void,true);
            })->sum(function($s){ return (float)($s->invoice_total_true ?? $s->usd_sell ?? 0); });

        $this->paidFromSales       = (float)$sales->sum('amount_paid');
        $this->paidFromCollections = (float)$sales->flatMap->collections->sum('amount');

        $this->paidTotal       = $this->paidFromSales + $this->paidFromCollections;
        $this->amountReceived  = $this->paidTotal;
        $this->remainingAmount = round($this->totalAmount - $this->paidTotal, 2);
    }


    public function render()
    {
return view('livewire.agency.show-collection-details-employee', [
    'availableBalanceToPayOthers' => $this->availableBalanceToPayOthers,
    'accountTypeLabel' => $this->accountTypeLabel,
])->layout('layouts.agency');


    }

    public function openEditAmountModal($saleId)
    {
        $this->sale = Sale::with(['customer','collections'])->findOrFail($saleId);
        $this->accountTypeLabel = $this->mapAccountType($this->sale->customer->account_type ?? null);
        $this->calculateAmounts(); // استدعاء الدالة الجديدة

        if ($this->remainingAmount <= 0) {
            session()->flash('message', 'تم سداد كامل المبلغ، لا يمكن التحصيل.');
            return;
        }

        $agencyId = Auth::user()->agency_id;
        $this->services = DynamicListItemSub::whereHas('parentItem', function($q) use ($agencyId) {
            $q->whereHas('dynamicList', function($q) use ($agencyId) {
                $q->where('name', 'قائمة الخدمات')
                  ->where(function($q) use ($agencyId) {
                      $q->where('agency_id', $agencyId)
                        ->orWhereNull('agency_id');
                  });
            });
        })->get()->map(function ($service) {
            return ['id' => $service->id, 'name' => $service->label, 'amount' => 0, 'paid' => 0];
        })->toArray();

        $this->showEditModal = true;
    }
public function saveAmounts()
{
    $totalServiceAmount = collect($this->services)->sum('amount');
    $payAmount = $this->payRemainingNow ?? 0;
    $totalToPay = $totalServiceAmount + $payAmount;

    $maxAllowed = $this->isPayToOthersMode ? $this->availableBalanceToPayOthers : $this->remainingAmount;

    if ($totalToPay > $maxAllowed) {
        if ($this->isPayToOthersMode) {
            $this->addError('amount', 'المبلغ المدخل يتجاوز رصيد الشركة لدى العميل!');
        } else {
            $this->addError('amount', 'المبلغ الكلي يتجاوز المتبقي!');
        }
        return;
    }


    if ($totalToPay <= 0) {
        $this->addError('amount', 'لا يوجد مبلغ لتحصيله.');
        return;
    }

    if ($this->isPayToOthersMode) {
        // هنا التعديل المهم: حفظ كائن التحصيل في متغير أولاً
        $newCollection = \App\Models\Collection::create([
            'agency_id' => $this->sale->agency_id,
            'sale_id' => $this->saleId,
            'amount' => $totalToPay,
            'payment_date' => now(),
            'note' => 'تسديد من رصيد الشركة للعميل.',
            'user_id' => Auth::id(),
        ]);

        // ثم تمرير المتغير للدالة
        $this->linkRefundToSourceSales($newCollection, $totalToPay);
    // لو اكتمل التحصيل للعملية الهدف نفك الدين إن وُجد
    app(\App\Services\EmployeeWalletService::class)
        ->releaseDebtAndPostCommission($newCollection->sale->fresh(['collections']));
    } else {
        $col = \App\Models\Collection::create([
            'agency_id' => $this->sale->agency_id,
            'sale_id' => $this->sale->id,
            'amount' => $totalToPay,
            'payment_date' => now(),
            'note' => 'تحصيل تلقائي لباقي المبلغ.',
            'user_id' => Auth::id(),
        ]);
            app(\App\Services\EmployeeWalletService::class)
        ->releaseDebtAndPostCommission($this->sale->fresh(['collections']));
    }

    $this->sale->refresh();
    $this->calculateAmounts();
    $this->recalculateAvailableBalance();
    
    $this->showEditModal = false;
    session()->flash('message', 'تم تسجيل التحصيل بنجاح.');
    $this->isPayToOthersMode = false;
    $this->updateCustomerSalesList();

}

protected function updateCustomerSalesList()
{
$cid = (int)$this->sale->customer_id;
$credit = max(0, $this->netForCustomer($cid));
$this->customerSales = $this->distributeCreditAsc(
    $this->buildCustomerSalesRows($cid), $credit
);

// حدِّث متبقي العملية الحالية إن لزم
$currentKey = $this->groupKey($this->sale);
if ($row = $this->customerSales->first(fn($r) => (($r->group_id ?? $r->id) == $currentKey))) {
    $this->remainingAmount = round(max(0,(float)$row->remaining), 2);
}

}

protected function recalculateAvailableBalance()
{
    // الصافي الموحّد للعميل. موجب = رصيد لصالح العميل يمكن استخدامه.
    $this->availableBalanceToPayOthers = max(0, $this->netForCustomer((int)$this->sale->customer_id));
}


    public function cancelEdit()
    {
        $this->reset(['showEditModal', 'services', 'payRemainingNow']);
        $this->isPayToOthersMode = false;

    }
    public $isPayToOthersMode = false;

public function openPayToOthersModal()
{
    if ($this->availableBalanceToPayOthers <= 0) {
        session()->flash('error', 'لا يوجد رصيد للعميل.');
        return;
    }

    $this->payToCustomerList = collect($this->customerSales)
        ->filter(fn($s) => ($s->remaining ?? 0) > 0)
        ->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->beneficiary_name ?? 'غير معروف',
        ])
        ->values()
        ->toArray();

    $this->selectedPayCustomerId = null;
    $this->reset(['totalAmount', 'paidFromSales', 'paidFromCollections', 'paidTotal', 'amountReceived', 'remainingAmount', 'payRemainingNow']);

    $this->isPayToOthersMode = true;
    $this->showEditModal = true;
}
public function updatedSelectedPayCustomerId($value)
{
    $value = (int) $value;

    $sale = Sale::with(['customer','collections'])
        ->where('agency_id', Auth::user()->agency_id)
        ->find($value);


    if (!$sale) {
        $this->reset(['saleId','totalAmount','paidFromSales','paidFromCollections','paidTotal','remainingAmount','payRemainingNow']);
        return;
    }

    // توحيد العميل
    if (!$sale->customer_id || $sale->customer_id !== $this->sale->customer_id) {
        $sale->customer_id = $this->sale->customer_id;
        $sale->save();
    }

    $this->sale   = $sale;
    $this->saleId = $sale->id;

// 1) احسب خام العملية
$this->calculateAmounts();

// 2) ابنِ قائمة المجموعات ووزّع الرصيد
$credit = max(0, $this->netForCustomer((int)$this->sale->customer_id));
$this->customerSales = $this->distributeCreditAsc(
    $this->buildCustomerSalesRows((int)$this->sale->customer_id), $credit
);

// 3) حدّد متبقي العملية المختارة بعد التوزيع
$currentKey = $this->groupKey($this->sale);
if ($row = $this->customerSales->first(fn($r) => (($r->group_id ?? $r->id) == $currentKey))) {
    $this->remainingAmount = round(max(0,(float)$row->remaining), 2);
}

// 4) قيود الدفع
$this->availableBalanceToPayOthers = $credit;
$this->payRemainingNow = max(0, min($this->remainingAmount, $this->availableBalanceToPayOthers));

}

protected function linkRefundToSourceSales($collection, $amountUsed)
{
    // الحصول على المبيعات التي لديها رصيد زائد (دين على الشركة)
    $salesWithCredit = collect($this->customerSales)->filter(function($s) {
        $remaining = $s->usd_sell - $s->amount_paid - $s->collections_total;
        return $remaining < 0;
    });

    if ($salesWithCredit->isNotEmpty()) {
        // تخزين معلومات العمليات المصدر في حقل الملاحظات
        $sourceNotes = $salesWithCredit->map(function($s) {
            return 'عملية #' . $s->id . ' (رصيد: ' . 
                   abs($s->usd_sell - $s->amount_paid - $s->collections_total) . ')';
        })->implode(' | ');

        $collection->update([
            'note' => $collection->note . " | تم السداد من: " . $sourceNotes
        ]);
    }
}


public function getCustomerCollectionsProperty()
{
    return Collection::with('user','sale')
        ->whereHas('sale', fn($q) =>
            $q->where('agency_id', Auth::user()->agency_id)
              ->where('customer_id', $this->sale->customer_id)
        )
        ->orderByDesc('payment_date')
        ->orderByDesc('id')
        ->paginate(10);
}


// إجمالي المديونية الحالية على العميل عبر كل المبيعات/المجموعات
public function getTotalDebtProperty(): float
{
    // الدين الحالي = الجزء السالب من الصافي الموحّد
    $net = $this->netForCustomer((int)$this->sale->customer_id);
    return $net < 0 ? abs($net) : 0.0;
}
 
private function netForCustomer(int $customerId): float
{
    $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
    $void   = ['void','cancel','canceled','cancelled'];

    $sumD = 0.0; // عليه
    $sumC = 0.0; // له

    // محفظة العميل
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

    // المبيعات + الاستردادات + الدفعات المسبقة
    $refundCreditKeys = [];
    $sales = Sale::where('customer_id', $customerId)->orderBy('created_at')->get();

    foreach ($sales as $s) {
        $st = mb_strtolower(trim((string)$s->status));

        if (!in_array($st,$refund,true) && !in_array($st,$void,true)) {
            $sumD += (float)($s->invoice_total_true ?? $s->usd_sell ?? 0);
        }

        if (in_array($st,$refund,true) || in_array($st,$void,true)) {
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

    // التحصيلات مع إزالة الازدواجية مع سحوبات المحفظة/الاسترداد
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
            // تجاهل إيداعات الاسترداد الآلية للمبيعات
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

private function minuteKey($dt): string
{
    try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i'); }
    catch (\Throwable $e) { return (string)$dt; }
}

private function moneyKey($n): string
{
    return number_format((float)$n, 2, '.', '');
}

private function groupKey($sale): string|int
{
    return $sale->sale_group_id ?? $sale->id;
}

private function buildCustomerSalesRows(int $customerId): \Illuminate\Support\Collection
{
    $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
    $void   = ['void','cancel','canceled','cancelled'];

    $raw = Sale::with(['employee','collections','serviceType'])
        ->where('agency_id', Auth::user()->agency_id)
        ->where('customer_id', $customerId)
        ->get()
        ->groupBy(fn($it) => $it->sale_group_id ?? $it->id);

    return $raw->map(function($sales) use($refund,$void){
        $first = $sales->first();

        $total = $sales->filter(function($s) use($refund,$void){
                    $st = mb_strtolower((string)$s->status);
                    return !in_array($st,$refund,true) && !in_array($st,$void,true);
                 })->sum(fn($s)=> (float)($s->invoice_total_true ?? $s->usd_sell ?? 0));

        $paidSales   = (float)$sales->sum('amount_paid');
        $paidCollect = (float)$sales->flatMap->collections->sum('amount');

        $remainingRaw = max(0.0, round($total - $paidSales - $paidCollect, 2));

        return (object)[
            'id'                   => $first->id,
            'group_id'             => $first->sale_group_id,
            'employee'             => $first->employee,
            'beneficiary_name'     => $first->beneficiary_name,
            'service_date'         => $first->service_date,
            'service_type_name'    => optional($first->serviceType)->label,
            'sale_date'            => $first->sale_date,
            'service'              => $first->service,
            'usd_sell'             => $total,
            'amount_paid'          => $paidSales,
            'collections_total'    => $paidCollect,
            'expected_payment_date'=> $first->expected_payment_date,
            'remaining_raw'        => $remainingRaw, // قبل توزيع الرصيد
            'remaining'            => $remainingRaw, // سيتم تعديلها
            'credit_applied'       => 0.0,
        ];
    })->values();
}

/**
 * يوزّع الرصيد الموجب على الديون تصاعديًا (الأقل فالأكثر).
 * لا يلمس الصفوف التي متبقيها الخام = 0.
 */
private function distributeCreditAsc(\Illuminate\Support\Collection $rows, float $credit)
{
    if ($credit <= 0) return $rows;

    $ordered = $rows->sortBy('remaining_raw')->values();

    foreach ($ordered as $r) {
        if ($credit <= 0) break;
        $need = (float)$r->remaining_raw;
        if ($need <= 0) { $r->remaining = 0.0; continue; }

        $apply = min($credit, $need);
        $r->credit_applied = round($apply, 2);
        $r->remaining      = round($need - $apply, 2);
        $credit            -= $apply;
    }

    return $ordered->sortByDesc('sale_date')->values(); // أو أي ترتيب عرض تفضّله
}

}