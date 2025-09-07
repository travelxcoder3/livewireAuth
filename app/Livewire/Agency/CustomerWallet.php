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

    protected $listeners = [
        'wallet-updated' => 'onWalletUpdated',
        'wallet-opened'  => 'onWalletOpened',
    ];

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

        $this->onWalletOpened($customerId);
    }

    public function getPageName() { return 'walletPage'; }

    public function close(): void { $this->dispatch('wallet-closed'); }

    public function submit()
    {
        $this->validate([
            'type' => 'required|in:deposit,withdraw,adjust',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
        ]);

        $typeAtSubmit = $this->type;

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
                'performed_by_name'=> auth()->user()->name ?? 'system',
            ]);

            $wallet->update(['balance' => $newBalance]);
        });

       $autoApplied = 0.0;
            if ($typeAtSubmit === 'deposit') {
                $autoApplied = app(\App\Services\CustomerCreditService::class)
                    ->autoPayAllFromWallet($this->customer, (float)$this->amount);
            }


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
            ->orderBy('created_at','desc')->orderBy('id','desc')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.agency.customer-wallet');
    }

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
    $cid = (int)$this->customerId;

    // 1) المشتريات + المدفوع
    $sales = \App\Models\Sale::where('customer_id', $cid)
        ->get(['usd_sell','invoice_total_true','status','amount_paid','refund_amount']);

    $debit  = 0.0; // عليه
    $credit = 0.0; // له

    foreach ($sales as $s) {
        $st = mb_strtolower((string)$s->status);
        $total = (float)($s->invoice_total_true ?? $s->usd_sell ?? 0);

        if ($st === 'void' || str_contains($st,'cancel')) {
            // تجاهل
        } elseif (str_contains($st,'refund')) {
            // الاسترداد يُحسب له
            $credit += (float)($s->refund_amount ?? 0) > 0
                ? (float)$s->refund_amount
                : abs((float)$s->usd_sell);
        } else {
            $debit  += max(0.0, $total);
        }

        $credit += max(0.0, (float)($s->amount_paid ?? 0));
    }

    // 2) التحصيلات كلها
    $credit += (float)\App\Models\Collection::whereHas('sale', fn($q)=>$q->where('customer_id',$cid))
                ->sum('amount');

    // 3) عمولات العميل من معاملات المحفظة فقط
    $wq = \App\Models\WalletTransaction::whereHas('wallet', fn($q)=>$q->where('customer_id',$cid));
    $credit += (float)(clone $wq)->where('type','deposit')
                ->where('reference','like','commission:group:%')->sum('amount');
    $debit  += (float)(clone $wq)->where('type','withdraw')
                ->where('reference','like','commission:group:%')->sum('amount');

    // 4) أي رصيد سالب بالمحفظة يُعد ديناً إضافياً
    $neg = max(0.0, -1 * (float)($this->wallet->balance ?? 0));

    $debtNow = max(0.0, ($debit + $neg) - $credit);
    return round($debtNow, 2);
}


    public function getDisplayBalanceProperty(): float
    {
        return max(0.0, (float)($this->wallet->balance ?? 0));
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

            $activeTotal = 0.0;
            if ($latestStatus !== 'void'
                && !str_contains($latestStatus, 'cancel')
                && !str_contains($latestStatus, 'refund')) {
                $activeTotal = (float)($latest->invoice_total_true ?? $latest->usd_sell ?? 0);
            }

            $refundTotal = $g->filter(fn($s) => str_contains(mb_strtolower((string)$s->status), 'refund'))
                ->sum(function ($s) {
                    $refund = (float)($s->refund_amount ?? 0);
                    if ($refund <= 0) $refund = abs((float)($s->usd_sell ?? 0));
                    return -1 * $refund;
                });

            $paid = max(0.0, (float)$g->sum('amount_paid'));
            $coll = max(0.0, (float)$g->sum(fn($s) => $s->collections->sum('amount')));
            $remaining = round(($activeTotal + $refundTotal) - $paid - $coll, 2);

            $ts = Carbon::parse($latest->created_at);

            $rows[] = [
                'group_id'    => (string)$gid,
                'latest_id'   => (int)$latest->id,
                'status'      => (string)$latest->status,
                'reference'   => (string)($latest->reference ?? ''),
                'route'       => (string)($latest->route ?? ''),
                'pnr'         => (string)($latest->pnr ?? ''),
                'active'      => round($activeTotal, 2),
                'refunds'     => round($refundTotal, 2),
                'paid'        => round($paid, 2),
                'collections' => round($coll, 2),
                'remaining'   => $remaining,
                'latest_ts'   => $ts->toDateTimeString(),
            ];
        }

        usort($rows, fn($a,$b) => ($b['remaining'] <=> $a['remaining']) ?: ($a['latest_id'] <=> $b['latest_id']));
        return $rows;
    }

    public function getUnifiedLedgerProperty(): array
    {
        $rows = [];

        foreach ($this->debtBreakdown as $g) {
            $gid = (string)$g['group_id'];
            $ts  = (string)$g['latest_ts'];

            $ref = trim(
                ($g['reference'] ?? '')
                .' '.($g['route'] ? '| '.$g['route'] : '')
                .($g['pnr'] ? ' | PNR: '.$g['pnr'] : '')
            );

            $withdrawTxs = \App\Models\WalletTransaction::where('wallet_id', $this->wallet->id)
                ->where('type', 'withdraw')
                ->where('reference', 'like', '%|group:'.$gid.'%')
                ->orderBy('id')
                ->get(['amount','performed_by_name','created_at','reference','running_balance']);

            $commTxs = \App\Models\WalletTransaction::where('wallet_id', $this->wallet->id)
                ->where('reference', 'commission:group:'.$gid)
                ->orderBy('id')
                ->get(['type','amount','performed_by_name','created_at','reference','running_balance']);

            $refundTxs = \App\Models\WalletTransaction::where('wallet_id', $this->wallet->id)
                ->where('type', 'deposit')
                ->where('reference', 'like', '%sales-auto|group:'.$gid.'%')
                ->orderBy('id')
                ->get(['amount','performed_by_name','created_at','reference','running_balance']);

            $firstTxAt = collect([
                optional($withdrawTxs->first())->created_at,
                optional($commTxs->first())->created_at,
                optional($refundTxs->first())->created_at,
            ])->filter()->min();

            $anchor = ($firstTxAt ?: \Carbon\Carbon::parse($ts))->toDateTimeString();
            $bts    = $anchor;   // مرساة كتلة المجموعة
            $debtTs = $anchor;   // البقاء في نفس الثانية

            $saleIds = \App\Models\Sale::where(function($q) use ($gid) {
                    $q->where('sale_group_id', $gid)->orWhere('id', $gid);
                })
                ->where('customer_id', $this->customerId)
                ->pluck('id');

            $collectionsNonWallet = (float) \App\Models\Collection::whereIn('sale_id', $saleIds)
                ->where('method', '!=', 'wallet')
                ->sum('amount');

            $displayDebtBeforeWallet = max(0.0, (float)$g['active']);
            $walletTargetDebt = max(0.0, (float)$g['active'] - (float)$g['paid'] - $collectionsNonWallet);

            $block = [];

            // (أ) عمولة العميل
            foreach ($commTxs as $tx) {
                $isDeposit = strtolower($tx->type) === 'deposit';
                $block[] = [
                    'ts'        => $debtTs,
                    'bts'       => $bts,
                    'seq'       => $isDeposit ? 1.12 : 1.18, // إيداع عمولة < خصم عمولة
                    'label'     => $isDeposit ? 'إضافة عمولة عميل' : 'تعديل عمولة عميل (خصم)',
                    'credit'    => $isDeposit ? (float)$tx->amount : 0.0,
                    'debit'     => $isDeposit ? 0.0 : (float)$tx->amount,
                    'reference' => 'commission:group:'.$gid,
                    'performed' => (string)($tx->performed_by_name ?? ''),
                    'kind'      => $isDeposit ? 'deposit' : 'withdraw_misc',
                    'running'   => null,
                    'tie'       => (int)$g['latest_id'],
                ];
            }

            // (ب) دين العملية قبل الخصم — أدنى أولوية داخل نفس الثانية
            if ($displayDebtBeforeWallet > 0) {
                $block[] = [
                    'ts'        => $debtTs,
                    'bts'       => $bts,
                    'seq'       => 1.00, // أقل من الإيداعات والسحوبات
                    'label'     => 'دين العملية قبل الخصم',
                    'credit'    => 0.0,
                    'debit'     => $displayDebtBeforeWallet,
                    'reference' => $ref,
                    'performed' => '',
                    'kind'      => 'debt_anchor',
                    'running'   => null,
                    'tie'       => (int)$g['latest_id'],
                ];
            }

            // (ج) سحب من الرصيد للسداد — أعلى من الإيداع
            $paidFromWallet = 0.0;
            foreach ($withdrawTxs as $tx) {
                $paidFromWallet += (float)$tx->amount;
                $gap = round($walletTargetDebt - $paidFromWallet, 2);

                $block[] = [
                    'ts'        => $tx->created_at?->toDateTimeString() ?? $anchor,
                    'bts'       => $bts,
                    'seq'       => 1.20,
                    'label'     => 'سحب من الرصيد للسداد',
                    'credit'    => 0.0,
                    'debit'     => (float)$tx->amount,
                    'reference' => (string)($tx->reference ?? ('sale:*|group:'.$gid)),
                    'performed' => (string)($tx->performed_by_name ?? ''),
                    'kind'      => 'withdraw_sale_info',
                    'running'   => ($gap > 0 ? -$gap : null),
                    'tie'       => (int)$g['latest_id'],
                ];
            }

            // (د) استرداد
            foreach ($refundTxs as $tx) {
                $block[] = [
                    'ts'        => $tx->created_at?->toDateTimeString() ?? $anchor,
                    'bts'       => $bts,
                    'seq'       => 1.14,
                    'label'     => 'استرداد',
                    'credit'    => (float)$tx->amount,
                    'debit'     => 0.0,
                    'reference' => (string)($tx->reference ?? ('sales-auto|group:'.$gid)),
                    'performed' => (string)($tx->performed_by_name ?? ''),
                    'kind'      => 'deposit',
                    'running'   => null,
                    'tie'       => (int)$g['latest_id'],
                ];
            }

            // ترتيب داخلي للكتلة (تصاعدي)
            usort($block, function($a,$b){
                $cmp = strcmp($a['ts'],$b['ts']);
                return $cmp !== 0 ? $cmp : (($a['seq'] ?? 0) <=> ($b['seq'] ?? 0));
            });

            $rows = array_merge($rows, $block);
        }

        // (هـ) العمليات اليدوية غير المرتبطة بمجموعات/عمولات/استردادات
        $miscTxs = \App\Models\WalletTransaction::where('wallet_id', $this->wallet->id)
            ->where(function($q){
                $q->whereNull('reference')
                  ->orWhere(function($qq){
                      $qq->where('reference', 'not like', 'commission:group:%')
                         ->where('reference', 'not like', '%sales-auto|group:%')
                         ->where('reference', 'not like', 'sale:%|group:%')
                         ->where('reference', '!=', 'auto-settle');
                  });
            })
            ->orderBy('id')
            ->get(['type','amount','performed_by_name','created_at','reference','running_balance']);

        foreach ($miscTxs as $tx) {
            $typeLower = strtolower((string)$tx->type);
            $tsRow = $tx->created_at?->toDateTimeString() ?? now()->toDateTimeString();

            if ($typeLower === 'adjust') {
                $rows[] = [
                    'ts'        => $tsRow,
                    'bts'       => $tsRow,
                    'seq'       => 5.03, // خارج سلم 1.xx عمداً
                    'label'     => 'تعديل رصيد',
                    'credit'    => 0.0,
                    'debit'     => 0.0,
                    'reference' => (string)($tx->reference ?? ''),
                    'performed' => (string)($tx->performed_by_name ?? ''),
                    'kind'      => 'adjust',
                    'running'   => (float)($tx->running_balance ?? 0),
                    'tie'       => 0,
                ];
            } else {
                $isDeposit = $typeLower === 'deposit';

                $label   = $isDeposit ? 'إيداع محفظة' : 'سحب محفظة';
                $kind    = $isDeposit ? 'deposit' : 'withdraw_misc';
                $running = null;

                if ($isDeposit && (string)$tx->reference === 'employee-collections') {
                    $label   = 'إيداع تحصيل';
                    $kind    = 'deposit_ec_info';
                    $running = -1 * (float) $this->debt;
                }

                $rows[] = [
                    'ts'        => $tsRow,
                    'bts'       => $tsRow,
                    'seq'       => $isDeposit ? 1.13 : 1.22, // 👈 إيداع يدوي أقل من سحب السداد
                    'label'     => $label,
                    'credit'    => $isDeposit ? (float)$tx->amount : 0.0,
                    'debit'     => $isDeposit ? 0.0 : (float)$tx->amount,
                    'reference' => (string)($tx->reference ?? ''),
                    'performed' => (string)($tx->performed_by_name ?? ''),
                    'kind'      => $kind,
                    'running'   => $running,
                    'tie'       => 0,
                ];
            }
        }

        /* -------- حساب الرصيد العام للمحفظة -------- */

        // 1) تصاعدي لحساب الرصيد الجاري بدقة: كتلة ثم زمن ثم seq ثم tie
        $ordered = $rows;
        usort($ordered, function($a,$b){
            if (($a['bts'] ?? '') !== ($b['bts'] ?? '')) return strcmp($a['bts'],$b['bts']);
            $cmp = strcmp($a['ts'],$b['ts']);        if ($cmp !== 0) return $cmp;
            $cmp = (($a['seq'] ?? 0) <=> ($b['seq'] ?? 0)); if ($cmp !== 0) return $cmp;
            return (($a['tie'] ?? 0) <=> ($b['tie'] ?? 0));
        });

        $net = 0.0;
        foreach ($ordered as &$r) {
            switch ($r['kind'] ?? null) {
                case 'adjust':
                    $net = (float)($r['running'] ?? $net);
                    break;

                case 'deposit':
                    $net += (float)($r['credit'] ?? 0);
                    break;

                case 'withdraw_misc':
                case 'withdraw_sale_info':
                    $net -= (float)($r['debit'] ?? 0);
                    break;

                case 'debt_anchor':
                case 'deposit_ec_info':
                    break;
            }

            if (!isset($r['running']) || $r['running'] === null) {
                $r['running'] = round($net, 2);
            }
        }
        unset($r);

        // 2) عرض تنازلي: كتلة ثم زمن ثم seq ثم tie
        usort($ordered, function($a,$b){
            if (($a['bts'] ?? '') !== ($b['bts'] ?? '')) return strcmp($b['bts'],$a['bts']);
            $cmp = strcmp($b['ts'],$a['ts']);        if ($cmp !== 0) return $cmp;
            $cmp = (($b['seq'] ?? 0) <=> ($a['seq'] ?? 0)); if ($cmp !== 0) return $cmp;
            return (($b['tie'] ?? 0) <=> ($a['tie'] ?? 0));
        });

        return $ordered;
    }

    public function onWalletUpdated($customerId = null)
    {
        if (is_array($customerId)) {
            $customerId = $customerId['customerId'] ?? null;
        }
        if ((int)$customerId !== (int)$this->customerId) {
            return;
        }
        $this->wallet->refresh();
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    public function onWalletOpened($payload = null): void
    {
        $customerId = is_array($payload) ? ($payload['customerId'] ?? null) : $payload;
        if ((int)$customerId !== (int)$this->customerId) return;

        $this->wallet->refresh();
        $this->resetPage();
        $this->dispatch('$refresh');
    }
}
