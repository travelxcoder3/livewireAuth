<?php
namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\{Sale, Collection, User, WalletTransaction};
use Illuminate\Support\Str;

class EmployeeCollectionsIndex extends Component
{
    use WithPagination;

    public $name = '';
    public $from = null;
    public $to = null;

    public function updatingName(){ $this->resetPage(); }
    public function updatingFrom(){ $this->resetPage(); }
    public function updatingTo(){ $this->resetPage(); }

    public function render()
    {
        // اجلب كل المبيعات مع التحصيلات للوكالة
        $sales = Sale::with(['employee','collections'=>fn($q)=>$q->latest()])
            ->where('agency_id', Auth::user()->agency_id)
           ->when($this->name, fn($q)=>$q->whereHas('employee',
    fn($qq)=>$qq->where('name','like',"%{$this->name}%")))
->get();

           

        // تجميع حسب الموظف وحساب المتبقي من مبيعاته
        $byEmp = $sales->groupBy('user_id')->map(function($empSales){
            $first = $empSales->first();
            $emp   = $first?->employee;
            // احسب المتبقي لكل مجموعة بيع (sale_group_id أو id)
            $customerIds = $empSales->pluck('customer_id')->unique()->filter();
            $remaining = 0.0;
            foreach ($customerIds as $cid) {
                $net = $this->netForCustomer((int)$cid); // نفس دالة Show
                if ($net < 0) { $remaining += abs($net); }
            }


            $lastCol = $empSales->flatMap->collections->sortByDesc('payment_date')->first();

            return (object)[
                    'id'                    => $emp?->id,            // ← أضفها

                'employee_id'           => $emp?->id,
                'employee_name'         => $emp?->name ?? 'غير معروف',
                'remaining_total'       => $remaining,
                'last_payment_at'       => optional($lastCol)->payment_date,
                'last_collection_amount'=> optional($lastCol)->amount,
                'last_collection_at'    => optional($lastCol)->payment_date,
            ];
        })->filter(function($row){
    $ok = true;
    if ($this->from) $ok = $ok && $row->last_payment_at >= $this->from;
    if ($this->to)   $ok = $ok && $row->last_payment_at <= $this->to;
    return $ok;
})->values();


        // أضف رقم تسلسلي
        $rows = $byEmp->map(function($r,$i){ $r->index=$i+1; return $r; });

        return view('livewire.agency.employee-collections-index', compact('rows'))
            ->layout('layouts.agency')->title('تحصيلات الموظفين');
    }


    public function resetFilters()
    {
        $this->name = '';
        $this->from = null;
        $this->to   = null;
    }

    private function netForCustomer(int $customerId): float
{
    $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
    $void   = ['void','cancel','canceled','cancelled'];

    $sumD = 0.0; // عليه
    $sumC = 0.0; // له

    // جميع معاملات محفظة العميل
    $walletTx = WalletTransaction::whereHas('wallet', fn($q)=>$q->where('customer_id', $customerId))
        ->orderBy('created_at')->get();

    // مفاتيح سحوبات المحفظة (لمعادلة التحصيلات)
    $walletWithdrawAvail = [];
    foreach ($walletTx as $t) {
        if (strtolower((string)$t->type) === 'withdraw') {
            $k = $this->minuteKey($t->created_at) . '|' . $this->moneyKey($t->amount);
            $walletWithdrawAvail[$k] = ($walletWithdrawAvail[$k] ?? 0) + 1;
        }
    }

    // المبيعات
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

    // التحصيلات (غير المقترنة بسحب محفظة أو باسترداد)
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

    // معاملات المحفظة (تجاهل إيداعات الاسترداد sales-auto)
    foreach ($walletTx as $tx) {
        $evt  = $this->minuteKey($tx->created_at);
        $k    = $evt . '|' . $this->moneyKey($tx->amount);
        $type = strtolower((string)$tx->type);
        $ref  = Str::lower((string)$tx->reference);

        if ($type === 'deposit') {
            if (Str::contains($ref, 'sales-auto|group:')) continue;
            if (($refundCreditKeys[$k] ?? 0) > 0) { $refundCreditKeys[$k]--; continue; }
            $sumC += (float)$tx->amount;
        } elseif ($type === 'withdraw') {
            if (($collectionKeys[$k] ?? 0) > 0) { $collectionKeys[$k]--; continue; }
            $sumD += (float)$tx->amount;
        }
    }

    return round($sumC - $sumD, 2); // الصافي (له − عليه)
}

private function minuteKey($dt): string {
    try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i'); }
    catch (\Throwable $e) { return (string)$dt; }
}
private function moneyKey($n): string {
    return number_format((float)$n, 2, '.', '');
}

}
