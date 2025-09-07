<?php
namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Services\AutoSettlementService;

use App\Models\{User, Sale, DynamicListItemSub, Wallet, WalletTransaction, Collection};
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmployeeCollectionsShow extends Component
{
    use WithPagination;

    public User $employee;
    public $searchCustomer = '';
    public $lastPayFrom = null;
    public $lastPayTo   = null;

    // نافذة السداد
    public $showPayModal = false;
    public $currentCustomerId = null;
    public $currentCustomerName = '';
    public $currentDebtType = null;
    public $currentResponseType = null;

    public $remaining = 0;
    public $paid_now = null;         // وسيلة الدفع (نقدي/حوالة/..)
    public $collector_method = null;  // طريقة التحصيل لاحتساب عمولة المُحصّل
    public $note = '';

    public function mount(User $user)
    {
        $this->employee = $user->load(['department','position']);
    }

    public function updatingSearchCustomer()
    { 
        $this->resetPage();
    }
    public function updatingLastPayFrom()
    { 
        $this->resetPage(); 
    }
    public function updatingLastPayTo()
    {
         $this->resetPage(); 
    }

    protected function baseSales()
    {
        return Sale::with([
                'customer',
                'collections'=>fn($q)=>$q->latest(),
                'collections.customerType','collections.debtType',
                'collections.customerResponse','collections.customerRelation',
            ])
            ->where('agency_id', Auth::user()->agency_id)
            ->where('user_id', $this->employee->id);
    }

    public function getCustomerRowsProperty()
    {
        $sales = $this->baseSales()
            ->when($this->searchCustomer, fn($q)=>$q->whereHas('customer',
                fn($qq)=>$qq->where('name','like',"%{$this->searchCustomer}%")))
            ->when($this->lastPayFrom, fn($q)=>$q->whereHas('collections',
                fn($qq)=>$qq->whereDate('payment_date','>=',$this->lastPayFrom)))
            ->when($this->lastPayTo, fn($q)=>$q->whereHas('collections',
                fn($qq)=>$qq->whereDate('payment_date','<=',$this->lastPayTo)))
            ->get();

        return $sales->groupBy('customer_id')->map(function($cs){
            $firstSale = $cs->first();
            $customer  = $firstSale?->customer;

            // 👈 الصافي (له − عليه) بنفس منطق كشف الحساب/المحفظة
            $net = $this->netForCustomer((int)($customer?->id));

            $lastCol  = $cs->flatMap->collections->sortByDesc('payment_date')->first();
            $baseDate = $lastCol?->payment_date ?? $cs->min('sale_date'); 
            $days     = $baseDate ? Carbon::parse($baseDate)->diffInDays(now(), false) : null;

            return (object)[
                'id'            => $customer?->id, 
                'customer_id'   => $customer?->id,
                'customer_name' => $customer?->name ?? '—',
                'phone'         => $customer?->phone ?? '—',
                'debt_amount'   => $net, // 👈 الآن سالب عند وجود دين
                'last_paid'     => optional($lastCol)->amount,
                'last_paid_at'  => optional($lastCol)->payment_date,
                'debt_age_days' => $days !== null ? max(0, (int)$days) : null,
                'account_type'  => $customer?->account_type ?? '-',
                'debt_type'     => optional($lastCol?->debtType)->label ?? '-',
                'response'      => optional($lastCol?->customerResponse)->label ?? '-',
                'relation'      => optional($lastCol?->customerRelation)->label ?? '-',
            ];
        })
        // أعرض فقط من لديهم دين (الصافي سالب)
        ->filter(fn($r)=>$r->debt_amount < 0)
        ->values();
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

    // معاملات المحفظة (تجاهل إيداعات sales-auto الخاصة بالاسترداد)
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

    public function openPay($customerId)
    {
        $row = $this->customerRows->firstWhere('customer_id',$customerId);
        if (!$row) return;

        $this->currentCustomerId = $customerId;
        $this->currentCustomerName = $row->customer_name;
        $this->remaining = abs($row->debt_amount); // لأن الصافي يُعرض سالباً عند وجود دين
        $this->paid_now  = null;
        $this->showPayModal = true;
    }

    
    public function render()
   {
                // لوائح الخيارات للحالة
                $debtTypes = DynamicListItemSub::whereHas('parentItem',fn($q)=>$q->where('label','نوع المديونية'))->get();
                $responseTypes = DynamicListItemSub::whereHas('parentItem',fn($q)=>$q->where('label','تجاوب العميل'))->get();

                return view('livewire.agency.employee-collections-show',[
                    'rows'=>$this->customerRows,
                    'debtTypes'=>$debtTypes,
                    'responseTypes'=>$responseTypes,
                ])->layout('layouts.agency')->title('تفاصيل تحصيلات: '.$this->employee->name);
    }

            public function resetFilters()
            {
                $this->searchCustomer = '';
                $this->lastPayFrom    = null;
                $this->lastPayTo      = null;

                // أجبر مكونات التاريخ على مسح حالتها في الواجهة
                $this->dispatch('filters-cleared');
            }
  public function savePay()
  {
             $this->validate([
                'paid_now'          => 'required|numeric|min:0.01|max:'.$this->remaining,
                'collector_method'  => 'required|integer|in:1,2,3,4,5,6,7,8', // الطرق المعرفة لديك
            ]);
            DB::transaction(function () {
                $wallet = Wallet::firstOrCreate(
                    ['customer_id' => $this->currentCustomerId],
                    ['balance' => 0, 'status' => 'active']
                );

                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
                $newBalance = (float)$wallet->balance + (float)$this->paid_now;
                \Log::info('UI.savePay.input', [
            'employee_id' => $this->employee->id,
            'customer_id' => $this->currentCustomerId,
            'collector_method' => $this->collector_method,
            'paid_now' => $this->paid_now,
                ]);
                    WalletTransaction::create([
                            'wallet_id'       => $wallet->id,
                            'type'            => 'deposit',
                            'amount'          => $this->paid_now,
                            'running_balance' => $newBalance,
                            'reference'       => 'employee-collections',
                            'note'            => trim('سداد عبر تحصيلات الموظفين'.($this->note ? ' - '.$this->note : '')),
                        ]);


                        $wallet->update(['balance' => $newBalance]);


                    });

            // تسوية فورية لإنشاء قيود collections وتحديث آخر سداد
            $customer = \App\Models\Customer::findOrFail($this->currentCustomerId);
            app(\App\Services\AutoSettlementService::class)->autoSettle(
                customer: $customer,
                performedByName: 'employee-collections',
                onlyEmployeeId: $this->employee->id,
                collectorUserId: Auth::id(),
                collectorMethod: ($this->collector_method !== null ? (int)$this->collector_method : null),
            );





            // تحديث الواجهة بدون استخدام $this->sale (غير موجود هنا)
            $this->showPayModal = false;
            $this->reset(['paid_now','note','currentDebtType','currentResponseType']);
            $this->resetPage();          // يعيد حساب القوائم
            $this->dispatch('$refresh'); // يجبر إعادة الرندر
            session()->flash('type', 'success'); // success | error | warning | info
            session()->flash('message', 'تم السداد وإنشاء قيود التحصيل وتحديث آخر سداد تلقائيًا.');
   }



}
