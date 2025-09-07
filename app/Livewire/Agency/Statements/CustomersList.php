<?php

namespace App\Livewire\Agency\Statements;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use App\Models\Collection;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Layout('layouts.agency')]
class CustomersList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true, keep: true)]
    public string $search = '';

    #[Url(as: 'type', history: true, keep: true)]
    public string $accountType = ''; // '', debit, credit

    #[Url(as: 'from', history: true, keep: true)]
    public string $fromDate = '';

    #[Url(as: 'to', history: true, keep: true)]
    public string $toDate = '';

    public float $totalRemainingForCustomer = 0.0; // عليه
    public float $totalRemainingForCompany  = 0.0; // له

    public array $accountTypeOptions = [
        'debit'  => 'مدين (على العميل)',
        'credit' => 'دائن (لصالح العميل)',
    ];

    public function updatingSearch()      { $this->resetPage(); }
    public function updatingAccountType() { $this->resetPage(); }
    public function updatingFromDate()    { $this->resetPage(); }
    public function updatingToDate()      { $this->resetPage(); }

    private function normalize(string $s): string
    {
        $s = str_replace('ـ','', trim($s));
        $map = ['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''];
        return mb_strtolower(strtr($s, $map));
    }

    /** احسب إحصائيات عميل: نفس منطقك القديم + تأثير المحفظة فقط */
    private function buildStats(Customer $c): array
    {
        $currency = Auth::user()->agency->currency ?? 'USD';

        // المبيعات + التحصيلات حسب التجميع
        $sales = $c->sales()->with('collections')->get(['id','sale_group_id','usd_sell','amount_paid','created_at']);

        $lastSale = $sales->sortByDesc(fn($s)=>[$s->created_at, $s->id])->first();
        $lastSaleDate = $lastSale?->created_at ? \Carbon\Carbon::parse($lastSale->created_at)->format('Y-m-d') : null;

        $grouped = $sales->groupBy(fn($s)=>$s->sale_group_id ?? $s->id);

        $remainingForCustomer = 0.0; // عليه
        $creditFromSales      = 0.0; // رصيد لصالح العميل من فرق المجموعات

        foreach ($grouped as $group) {
            $sell = $group->sum('usd_sell');
            $paid = $group->sum('amount_paid');
            $col  = $group->flatMap->collections->sum('amount');
            $rem  = $sell - $paid - $col;

            if ($rem > 0)  $remainingForCustomer += $rem;   // عليه
            if ($rem < 0)  $creditFromSales     += abs($rem); // له (خام)
        }

        // خصم الرصيد المستخدم لتسديد آخرين (لو وُسم بالملاحظة التالية)
        $usedCredit = Collection::whereHas('sale', fn($q)=>$q->where('customer_id', $c->id))
            ->where('note', 'like', '%تسديد من رصيد الشركة للعميل%')
            ->sum('amount');

        $remainingForCompany = max(0.0, $creditFromSales - (float)$usedCredit); // له (من المبيعات فقط)

        // ==== تأثير المحفظة فقط بدون تغيير منطق المبيعات ====
        // deposit/adjust(+) => له ، withdraw/adjust(-) => عليه
        $walletNet = WalletTransaction::whereHas('wallet', fn($q)=>$q->where('customer_id', $c->id))
            ->get(['type','amount'])
            ->reduce(function($carry, $tx){
                $t = strtolower((string)$tx->type);
                $amt = (float)$tx->amount;
                if ($t === 'deposit')      return $carry + $amt; // +
                if ($t === 'withdraw')     return $carry - $amt; // -
                if ($t === 'adjust')       return $carry + $amt; // موجب/سالب
                return $carry;
            }, 0.0);

        if ($walletNet > 0) {               // زيادة رصيد العميل لدى الشركة
            $remainingForCompany += $walletNet;    // له
        } elseif ($walletNet < 0) {         // سحب من المحفظة
            $remainingForCustomer += abs($walletNet); // عليه
        }
        // ================================================
        // --- NEW: احسب الصافي (له − عليه) بنفس منطق صفحة التفاصيل ---
        $minuteKey = function($dt){ try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i'); } catch (\Throwable $e) { return (string)$dt; } };
        $moneyKey  = function($n){ return number_format((float)$n, 2, '.', ''); };

        $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
        $void   = ['void','cancel','canceled','cancelled'];

        $sumD = 0.0; // عليه
        $sumC = 0.0; // له

        // معاملات المحفظة
        $walletTx = WalletTransaction::whereHas('wallet', fn($q)=>$q->where('customer_id', $c->id))
            ->orderBy('created_at')->get();

        $walletWithdrawAvail = [];
        foreach ($walletTx as $t) {
            if (strtolower((string)$t->type) === 'withdraw') {
                $k = $minuteKey($t->created_at).'|'.$moneyKey($t->amount);
                $walletWithdrawAvail[$k] = ($walletWithdrawAvail[$k] ?? 0) + 1;
            }
        }

        // المبيعات
        $refundCreditKeys = [];
        $salesAll = \App\Models\Sale::where('customer_id', $c->id)->orderBy('created_at')->get();

        foreach ($salesAll as $s) {
            $st = mb_strtolower(trim((string)$s->status));

            if (!in_array($st, $refund, true) && !in_array($st, $void, true)) {
                $sumD += (float)($s->invoice_total_true ?? $s->usd_sell ?? 0);
            }

            if (in_array($st, $refund, true) || in_array($st, $void, true)) {
                $amt = (float)($s->refund_amount ?? 0);
                if ($amt <= 0) $amt = abs((float)($s->usd_sell ?? 0));
                $sumC += $amt;

                $keyR = $minuteKey($s->created_at).'|'.$moneyKey($amt);
                $refundCreditKeys[$keyR] = ($refundCreditKeys[$keyR] ?? 0) + 1;
            }

            if ((float)$s->amount_paid > 0) {
                $sumC += (float)$s->amount_paid;
            }
        }

        // التحصيلات
        $collections = Collection::with('sale')
            ->whereHas('sale', fn($q)=>$q->where('customer_id', $c->id))
            ->orderBy('created_at')->get();

        $collectionKeys = [];
        foreach ($collections as $col) {
            $evt = $minuteKey($col->created_at ?? $col->payment_date);
            $k   = $evt.'|'.$moneyKey($col->amount);
            $collectionKeys[$k] = ($collectionKeys[$k] ?? 0) + 1;
        }

        foreach ($collections as $col) {
            $evt = $minuteKey($col->created_at ?? $col->payment_date);
            $k   = $evt.'|'.$moneyKey($col->amount);

            if (($refundCreditKeys[$k] ?? 0) > 0) { $refundCreditKeys[$k]--; continue; }
            if (($walletWithdrawAvail[$k] ?? 0) > 0) { $walletWithdrawAvail[$k]--; continue; }

            $sumC += (float)$col->amount;
        }

        // إدراج المحفظة في الإجماليات مع تجنّب التكرار
        foreach ($walletTx as $tx) {
            $evt  = $minuteKey($tx->created_at);
            $k    = $evt.'|'.$moneyKey($tx->amount);
            $type = strtolower((string)$tx->type);
            $ref  = Str::lower((string)$tx->reference);

            if ($type === 'deposit') {
                if (Str::contains($ref, 'sales-auto|group:')) continue;        // إيداع استرداد سيظهر في "استرداد"
                if (($refundCreditKeys[$k] ?? 0) > 0) { $refundCreditKeys[$k]--; continue; }
                $sumC += (float)$tx->amount;
            } elseif ($type === 'withdraw') {
                if (($collectionKeys[$k] ?? 0) > 0) { $collectionKeys[$k]--; continue; } // سحب يقابل تحصيلاً
                $sumD += (float)$tx->amount;
            }
        }

        $netStrict = round($sumC - $sumD, 2); // ← نفس الصافي في صفحة التفاصيل
        // -------------------------------------------------------------------

        return [
            'currency'               => $currency,
            'remaining_for_customer' => (float)$remainingForCustomer, // تُستخدم لبطاقات الإجماليات كما هي
            'remaining_for_company'  => (float)$remainingForCompany,  // تُستخدم لبطاقات الإجماليات كما هي
            'net_balance'            => (float)$netStrict,            // عمود "الإجمالي" = الصافي (له − عليه)
            'last_sale_date'         => $lastSaleDate,
        ];

    }

    /** فلترة حسب تاريخ آخر عملية بيع فقط */
    private function passDateFilter(?string $lastSaleDate): bool
    {
        if (!$this->fromDate && !$this->toDate) return true;
        if (!$lastSaleDate) return false;

        $d = \Carbon\Carbon::parse($lastSaleDate)->startOfDay();
        $from = $this->fromDate ? \Carbon\Carbon::parse($this->fromDate)->startOfDay() : null;
        $to   = $this->toDate   ? \Carbon\Carbon::parse($this->toDate)->endOfDay()   : null;

        if ($from && $d->lt($from)) return false;
        if ($to   && $d->gt($to))   return false;
        return true;
    }

    private function baseQuery()
    {
        $term = $this->normalize($this->search);

        return Customer::where('agency_id', Auth::user()->agency_id)
            ->when($term !== '', function($q) use($term){
                $sql = "LOWER(name)";
                foreach (['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''] as $f=>$t) {
                    $sql = "REPLACE($sql,'$f','$t')";
                }
                $q->whereRaw("$sql LIKE ?", ['%'.$term.'%']);
            })
            ->orderByDesc('id');
    }

    public function resetFilters(): void
    {
        $this->search      = '';
        $this->accountType = '';
        $this->fromDate    = '';
        $this->toDate      = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->baseQuery();
        $all   = (clone $query)->get();

        // ابنِ إحصائيات كل عميل مرة واحدة
        $statsById = [];
        foreach ($all as $c) $statsById[$c->id] = $this->buildStats($c);

        // IDs بعد تطبيق فلتر التاريخ ونوع الحساب على صافي الإجمالي
        $filteredIds = collect($all)->filter(function($c) use ($statsById){
            $st = $statsById[$c->id] ?? null;
            if (!$st) return false;
            if (!$this->passDateFilter($st['last_sale_date'])) return false;
            if ($this->accountType === 'debit'  && $st['net_balance'] <= 0) return false;
            if ($this->accountType === 'credit' && $st['net_balance'] >= 0) return false;
            return true;
        })->pluck('id')->all();

        if ($this->accountType !== '' || $this->fromDate || $this->toDate) {
            $query->whereIn('id', $filteredIds ?: [-1]);
        }

        // بطاقات الإجماليات حسب الصافي (له − عليه) تمامًا مثل عمود "الإجمالي"
        $this->totalRemainingForCustomer = 0.0; // على العملاء (مجموع القيم السالبة كقيمة موجبة)
        $this->totalRemainingForCompany  = 0.0; // لصالح العملاء (مجموع القيم الموجبة)

        $idsForTotals = $filteredIds ?: $all->pluck('id');
        foreach ($idsForTotals as $id) {
            $st = $statsById[$id] ?? null;
            if (!$st) continue;

            $net = (float)($st['net_balance'] ?? 0); // نفس الذي نعرضه في الجدول
            if ($net > 0) {
                // له (لصالح العميل)
                $this->totalRemainingForCompany += $net;
            } elseif ($net < 0) {
                // عليه (على العميل)
                $this->totalRemainingForCustomer += abs($net);
            }
        }

        $this->totalRemainingForCustomer = round($this->totalRemainingForCustomer, 2);
        $this->totalRemainingForCompany  = round($this->totalRemainingForCompany, 2);


        // إخراج الصفحة
        $customers = $query->paginate(10)->through(function ($c) use ($statsById) {
            $st = $statsById[$c->id];
            $c->details_url       = route('agency.statements.customer', $c->id);
            $c->currency          = $st['currency'];
            $c->last_sale_date    = $st['last_sale_date'] ?: '-';
            $c->balance_total     = abs($st['net_balance']); 
            $c->account_type_text = $st['net_balance'] > 0 ? 'مدين' : ($st['net_balance'] < 0 ? 'دائن' : 'متزن');

            return $c;
        });

        return view('livewire.agency.statements.customers-list', [
            'customers'                 => $customers,
            'accountTypeOptions'        => $this->accountTypeOptions,
            'totalRemainingForCustomer' => $this->totalRemainingForCustomer,
            'totalRemainingForCompany'  => $this->totalRemainingForCompany,
        ]);
    }
}
