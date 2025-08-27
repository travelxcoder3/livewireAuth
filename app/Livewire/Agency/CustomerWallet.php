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
    'wallet-updated'   => 'onWalletUpdated',   // لو وصلك إشعار من صفحة المبيعات
    'wallet-opened'    => 'onWalletOpened',    // 👈 جديد: عند فتح المحفظة
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

        // 👈 تحديث فوري عند الفتح (بدون انتظار أي حدث خارجي)
        $this->onWalletOpened($customerId);
    }

    // لمنع تعارض الترقيم مع صفحات أخرى تستخدم WithPagination
    public function getPageName()
    {
        return 'walletPage';
    }
public function close(): void
{
    $this->dispatch('wallet-closed');
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
            'performed_by_name'=> auth()->user()->name ?? 'system',
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

        // مرجع نصي مختصر
        $ref = trim(
            ($g['reference'] ?? '')
            .' '.($g['route'] ? '| '.$g['route'] : '')
            .($g['pnr'] ? ' | PNR: '.$g['pnr'] : '')
        );

        // حركات مرتبطة بالمجموعة
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

        // مرساة زمنية واحدة لعرض كتلة المجموعة
        $anchorTs = null;
        foreach ([
            optional($withdrawTxs->first())->created_at,
            optional($commTxs->first())->created_at,
            optional($refundTxs->first())->created_at,
        ] as $cand) {
            if ($cand && (!$anchorTs || $cand->lt($anchorTs))) $anchorTs = $cand;
        }
        $anchor = ($anchorTs ?: \Carbon\Carbon::parse($ts))->toDateTimeString();


        // احسب دين العملية "قبل خصم المحفظة"
        $saleIds = \App\Models\Sale::where(function($q) use ($gid) {
                $q->where('sale_group_id', $gid)->orWhere('id', $gid);
            })
            ->where('customer_id', $this->customerId)
            ->pluck('id');

        $collectionsNonWallet = (float) \App\Models\Collection::whereIn('sale_id', $saleIds)
            ->where('method', '!=', 'wallet')
            ->sum('amount');

        $debtBeforeWallet = max(0.0, (float)$g['active'] - (float)$g['paid'] - $collectionsNonWallet);

        // بناء كتلة الصفوف للمجموعة
        $block = [];

// (أ) عمولة العميل
foreach ($commTxs as $tx) {
    $isDeposit = strtolower($tx->type) === 'deposit';

    $block[] = [
        // الإضافة بوقت البيع، الخصم بوقته الحقيقي (عادة مع الاسترداد)
        'ts'        => $isDeposit
            ? $anchor
            : ($tx->created_at?->toDateTimeString() ?? $anchor),

        // ترتيب داخل نفس الثانية:
        // 1.01 = إضافة عمولة (قبل دين/سحب)
        // 1.24 = خصم عمولة (يأتي مباشرة قبل صف "استرداد" 1.25)
        'seq'       => $isDeposit ? 1.01 : 1.24,

        'label'     => $isDeposit ? 'إضافة عمولة عميل' : 'تعديل عمولة عميل (خصم)',
        'credit'    => $isDeposit ? (float)$tx->amount : 0.0,
        'debit'     => $isDeposit ? 0.0 : (float)$tx->amount,
        'reference' => 'commission:group:'.$gid,
        'performed' => (string)($tx->performed_by_name ?? ''),
        'kind'      => $isDeposit ? 'deposit' : 'withdraw_misc',
        'running'   => null,
    ];
}


        // (ب) دين العملية قبل الخصم — مرجع بصري فقط
        if ($debtBeforeWallet > 0) {
            $block[] = [
                'ts'        => $anchor, 
                'seq'       => 1.10,
                'label'     => 'دين العملية قبل الخصم',
                'credit'    => 0.0,
                'debit'     => $debtBeforeWallet,
                'reference' => $ref,
                'performed' => '',
                'kind'      => 'debt_anchor',   // لا يؤثر على صافي المحفظة
                'running'   => null,            // ✅ اتركه null ليُعرض رصيد المحفظة كما هو
            ];
        }

        // (ج) سحب من الرصيد للسداد — معلومة فقط
// (ج) سحب من الرصيد للسداد — معلومة فقط
$paidFromWallet = 0.0;
foreach ($withdrawTxs as $tx) {
    $paidFromWallet += (float)$tx->amount;

    // المتبقي من الدين بعد السحوبات الحالية في نفس المجموعة
    $gap = round($debtBeforeWallet - $paidFromWallet, 2);

    $block[] = [
        'ts'        => $anchor,
        'seq'       => 1.20,
        'label'     => 'سحب من الرصيد للسداد',
        'credit'    => 0.0,
        'debit'     => (float)$tx->amount,
        'reference' => (string)($tx->reference ?? ('sale:*|group:'.$gid)),
        'performed' => (string)($tx->performed_by_name ?? ''),
        'kind'      => 'withdraw_sale_info',
        // إن بقي دين نُظهره بالسالب (مثال: -1090). إذا لا يوجد دين نتركها null لعرض رصيد المحفظة الفعلي.
        'running'   => ($gap > 0 ? -$gap : null),
    ];
}

        // (د) استرداد (إن وجد) — استخدم تاريخ الإنشاء الحقيقي للحفاظ على الترتيب الزمني العام
        foreach ($refundTxs as $tx) {
            $block[] = [
                'ts'        => $tx->created_at?->toDateTimeString() ?? $anchor,
                'seq'       => 1.25, // أي رقم عادي؛ المهم عدم تثبيته كآخر عنصر
                'label'     => 'استرداد',
                'credit'    => (float)$tx->amount,
                'debit'     => 0.0,
                'reference' => (string)($tx->reference ?? ('sales-auto|group:'.$gid)),
                'performed' => (string)($tx->performed_by_name ?? ''),
                'kind'      => 'deposit',
                'running'   => null,
            ];
        }


        // فرز داخل المجموعة (تصاعدي بالثانية ثم seq)
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
                 ->where('reference', '!=', 'auto-settle'); // 👈 لا تعرض سحوبات التسوية الفورية
          });
    })
    ->orderBy('id')
    ->get(['type','amount','performed_by_name','created_at','reference','running_balance']);


foreach ($miscTxs as $tx) {
    $isDeposit = strtolower($tx->type) === 'deposit';

    // الافتراض
    $label   = $isDeposit ? 'إيداع محفظة' : 'سحب محفظة';
    $kind    = $isDeposit ? 'deposit' : 'withdraw_misc';
    $running = null;

    // 👈 تخصيص عرض إيداع التحصيل
    if ($isDeposit && (string)$tx->reference === 'employee-collections') {
        $label   = 'إيداع تحصيل';
        $kind    = 'deposit_ec_info';          // معلومات فقط، لا تؤثر على صافي المحفظة
        $running = -1 * (float) $this->debt;   // المتبقي بعد التسوية (يظهر بالسالب)
    }

    $rows[] = [
        'ts'        => $tx->created_at?->toDateTimeString() ?? now()->toDateTimeString(),
        'seq'       => $isDeposit ? 1.15 : 1.14,
        'label'     => $label,
        'credit'    => $isDeposit ? (float)$tx->amount : 0.0,
        'debit'     => $isDeposit ? 0.0 : (float)$tx->amount,
        'reference' => (string)($tx->reference ?? ''),
        'performed' => (string)($tx->performed_by_name ?? ''),
        'kind'      => $kind,
        'running'   => $running,
    ];
}

    /* -------- حساب الرصيد العام للمحفظة -------- */

    // 1) تصاعدي لحساب الصافي
    $ordered = $rows;
    usort($ordered, function($a,$b){
        $cmp = strcmp($a['ts'],$b['ts']); // أقدم → أحدث
        return $cmp !== 0 ? $cmp : (($a['seq'] ?? 0) <=> ($b['seq'] ?? 0));
    });

    $net = 0.0;
    foreach ($ordered as &$r) {
switch ($r['kind'] ?? null) {
    case 'deposit':            $net += (float)($r['credit'] ?? 0); break;
    case 'withdraw_misc':      $net -= (float)($r['debit']  ?? 0); break;
    case 'withdraw_sale_info': $net -= (float)($r['debit']  ?? 0); break;
    case 'debt_anchor':
    case 'deposit_ec_info':    /* معلومات فقط: لا تغيّر صافي المحفظة */ break;
}

        if (!isset($r['running']) || $r['running'] === null) {
            $r['running'] = round($net, 2);
        }
    }

    unset($r);

    // 3) عرض تنازلي (الأحدث أعلى)
    usort($ordered, function($a,$b){
        $cmp = strcmp($b['ts'],$a['ts']);             // الأحدث أولاً
        return $cmp !== 0 ? $cmp : (($b['seq'] ?? 0) <=> ($a['seq'] ?? 0)); // ← اجعلها DESC
    });


    return $ordered;
}

public function onWalletUpdated($customerId = null)
{
    // للتوافق لو جاء Array بالغلط
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
