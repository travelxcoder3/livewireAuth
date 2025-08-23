<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\{Customer, Wallet, WalletTransaction, Sale};
use Carbon\Carbon;

class CustomerWallet extends Component
{
    use WithPagination;

    public int $customerId;
    public ?Customer $customer = null;
    public ?Wallet $wallet = null;

    // نموذج العملية
    public $type = 'deposit'; // deposit|withdraw|adjust
    public $amount;
    public $reference;
    public $note;

    // فلاتر الكشف
    public $from;
    public $to;
    public $q = '';

    public function mount(int $customerId)
    {
        $this->customerId = $customerId;
        $this->customer = Customer::with('agency')->findOrFail($customerId);
        $this->wallet = Wallet::firstOrCreate(['customer_id' => $customerId], [
            'balance' => 0, 'status' => 'active'
        ]);
    }

public function submit()
{
    $this->validate([
        'type' => 'required|in:deposit,withdraw,adjust',
        'amount' => 'required|numeric|min:0.01',
        'reference' => 'nullable|string|max:255',
        'note' => 'nullable|string|max:2000',
    ]);

    // 1) احفظ نوع العملية قبل أي تعديل
    $typeAtSubmit = $this->type;

    // 2) منطق الرصيد فقط داخل الترانزاكشن
    DB::transaction(function () {
        $wallet = Wallet::where('id', $this->wallet->id)->lockForUpdate()->first();

        $delta = (float) $this->amount;
        $newBalance = match ($this->type) {
            'deposit'  => $wallet->balance + $delta,
            'withdraw' => function () use ($wallet, $delta) {
                if ($wallet->balance < $delta) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'الرصيد غير كافٍ',
                    ]);
                }
                return $wallet->balance - $delta;
            },
            'adjust'   => $delta, // رصيد نهائي
        };
        if (is_callable($newBalance)) $newBalance = $newBalance();

        $txType = $this->type === 'adjust' ? 'adjust' : $this->type;

        WalletTransaction::create([
            'wallet_id'        => $wallet->id,
            'type'             => $txType,
            'amount'           => $this->type === 'adjust'
                                    ? abs($newBalance - $wallet->balance)
                                    : $delta,
            'running_balance'  => $newBalance,
            'reference'        => $this->reference ?: null,
            'note'             => $this->note ?: null,
        ]);

        $wallet->update(['balance' => $newBalance]);
    });

  // 3) تشغيل السداد من المحفظة بعد الإيداع
$autoApplied = 0.0;
if ($typeAtSubmit === 'deposit') {
    $autoApplied = app(\App\Services\CustomerCreditService::class)
        ->autoPayAllFromWallet($this->customer);
}


    // 4) تحديث الواجهة
    $this->wallet->refresh();
    $this->reset(['type','amount','reference','note']);
    $this->type = 'deposit';

    session()->flash('message',
        $autoApplied > 0
        ? 'تم الإيداع وتصفية بقيمة ' . number_format($autoApplied, 2)
        : 'تم تنفيذ العملية'
    );
}


    public function getTransactionsProperty()
    {
        return WalletTransaction::where('wallet_id', $this->wallet->id)
            ->when($this->from, fn($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn($q)   => $q->whereDate('created_at', '<=', $this->to))
            ->when($this->q, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('reference', 'like', "%{$this->q}%")
                       ->orWhere('note', 'like', "%{$this->q}%")
                       ->orWhere('performed_by_name', 'like', "%{$this->q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.agency.customer-wallet');
    }

    // CustomerWallet.php
public function updatingFrom() { $this->resetPage(); }
public function updatingTo()   { $this->resetPage(); }
public function updatingQ()    { $this->resetPage(); }


public function runAutoSettle()
{
    $applied = app(\App\Services\AutoSettlementService::class)
        ->autoSettle($this->customer, auth()->user()->name ?? 'Auto-Settle');

    $this->wallet->refresh();
    session()->flash('message', $applied > 0 ? 'تمت التصفية: '.number_format($applied,2) : 'لا يوجد ما يُصفّى');
}



public function getDebtProperty(): float
{
    $groups = Sale::with('collections')
        ->where('customer_id', $this->customerId)
        ->get()
        ->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

    $debt = 0.0;

    $effectiveTotal = function ($s): float {
        $status = mb_strtolower(trim((string)$s->status));

        // الإلغاء لا يؤثر
        if ($status === 'void' || str_contains($status, 'cancel')) {
            return 0.0;
        }

        // الاسترداد يقلّل صافي المجموعة
        if (str_contains($status, 'refund')) {
            $refund = (float) ($s->refund_amount ?? 0);
            if ($refund <= 0) {
                $refund = abs((float) ($s->usd_sell ?? 0)); // عندك يكون سالب
            }
            return -1 * $refund;
        }

        // العادي/المعاد إصدارُه
        return (float) ($s->invoice_total_true ?? $s->usd_sell ?? 0);
    };

    foreach ($groups as $g) {
        $remaining = $g->sum(function ($s) use ($effectiveTotal) {
            $total  = $effectiveTotal($s);
            $paid   = max(0.0, (float) ($s->amount_paid ?? 0));
            $coll   = max(0.0, (float) $s->collections->sum('amount'));
            return $total - $paid - $coll;
        });

        if ($remaining > 0) {
            $debt += $remaining;
        }
    }

    return round($debt, 2);
}



public function getDebtBreakdownProperty(): array
{
    $groups = \App\Models\Sale::with('collections')
        ->where('customer_id', $this->customerId)
        ->orderBy('id')
        ->get()
        ->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

    $rows = [];

    foreach ($groups as $gid => $g) {
        $latest = $g->last();
        $latestStatus = mb_strtolower((string)$latest->status);

        // الإجمالي الفعّال للمجموعة = آخر سجل “نشط”
        $activeTotal = 0.0;
        if ($latestStatus !== 'void'
            && !str_contains($latestStatus, 'cancel')
            && !str_contains($latestStatus, 'refund')) {
            $activeTotal = (float)($latest->invoice_total_true ?? $latest->usd_sell ?? 0);
        }

        // إجمالي الاستردادات داخل المجموعة (بالسالب)
        $refundTotal = $g->filter(fn($s) => str_contains(mb_strtolower((string)$s->status), 'refund'))
            ->sum(function ($s) {
                $refund = (float)($s->refund_amount ?? 0);
                if ($refund <= 0) $refund = abs((float)($s->usd_sell ?? 0));
                return -1 * $refund;
            });

        $paid = max(0.0, (float)$g->sum('amount_paid'));
        $coll = max(0.0, (float)$g->sum(fn($s) => $s->collections->sum('amount')));
        $remaining = round(($activeTotal + $refundTotal) - $paid - $coll, 2);

        $ts = $latest->sale_date
            ? Carbon::parse($latest->sale_date.' 00:00:00')
            : Carbon::parse($latest->created_at);

        $rows[] = [
            'group_id'    => (string)$gid,
            'latest_id'   => (int)$latest->id,
            'status'      => (string)$latest->status,
            'reference'   => (string)($latest->reference ?? ''),
            'route'       => (string)($latest->route ?? ''),
            'pnr'         => (string)($latest->pnr ?? ''),
            'active'      => round($activeTotal, 2),        // إجمالي عليه من المبيعات
            'refunds'     => round($refundTotal, 2),        // يعود له
            'paid'        => round($paid, 2),               // مدفوع داخل السجل
            'collections' => round($coll, 2),               // تحصيلات
            'remaining'   => $remaining,                    // = active - (paid+collections+refunds)
            'latest_ts'   => $ts->toDateTimeString(),       // 👈 للتسلسل الزمني
        ];
    }

    // الأهم تظهر المجموعات التي عليها متبقٍ أولاً
    usort($rows, fn($a,$b) => ($b['remaining'] <=> $a['remaining']) ?: ($a['latest_id'] <=> $b['latest_id']));
    return $rows;
}


public function getUnifiedLedgerProperty(): array
{
    $rows = [];

    foreach ($this->debtBreakdown as $g) {
        $gid = (string)$g['group_id'];
        $ts  = (string)$g['latest_ts'];
        $ref = trim(($g['reference'] ?? '').' '.($g['route'] ? '| '.$g['route'] : '').($g['pnr'] ? ' | PNR: '.$g['pnr'] : ''));

        // 1) عمولة هذه المجموعة (إيداعات commission:group:<gid>)
        $commission = (float) \App\Models\WalletTransaction::where('wallet_id', $this->wallet->id)
            ->where('type','deposit')
            ->where('reference','commission:group:'.$gid)
            ->sum('amount');

        // 2) مبالغ السداد من المحفظة لهذه المجموعة (سحوبات مرجعها يحتوي |group:<gid>)
        $walletApplied = (float) \App\Models\WalletTransaction::where('wallet_id', $this->wallet->id)
            ->where('type','withdraw')
            ->where('reference','like','%|group:'.$gid.'%')
            ->sum('amount');

        // 3) دين العملية قبل الخصم من المحفظة
        // = إجمالي البيع - المدفوع داخل السجل - تحصيلات غير المحفظة
        $collectionsNonWallet = max(0.0, (float)$g['collections'] - $walletApplied);
        $debtBeforeWallet = max(0.0, (float)$g['active'] - (float)$g['paid'] - $collectionsNonWallet);

        // الأسطر الثلاثة بالترتيب المطلوب
        $rows[] = [
            'ts'=>$ts, 'seq'=>1, 'label'=>'عمولة هذه العملية',
            'credit'=>$commission, 'debit'=>0.0,
            'reference'=>'commission:group:'.$gid, 'performed'=>'',
        ];

        $rows[] = [
            'ts'=>$ts, 'seq'=>2, 'label'=>'دين العملية قبل الخصم',
            'credit'=>0.0, 'debit'=>$debtBeforeWallet,
            'reference'=>$ref, 'performed'=>'',
        ];

        $rows[] = [
            'ts'=>$ts, 'seq'=>3, 'label'=>'خصم من العمولة',
            'credit'=>0.0, 'debit'=>$walletApplied,
            'reference'=>'sale:*|group:'.$gid, 'performed'=>'',
        ];
    }

    // ترتيب: أحدث وقت أولاً، ثم seq 1→3
    usort($rows, function($a,$b){
        $cmp = strcmp($b['ts'],$a['ts']);
        return $cmp !== 0 ? $cmp : (($a['seq'] ?? 0) <=> ($b['seq'] ?? 0));
    });

    return $rows;
}





}
