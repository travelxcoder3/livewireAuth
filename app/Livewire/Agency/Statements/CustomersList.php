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

        $net = $remainingForCustomer - $remainingForCompany; // يُعرض كالإجمالي

        return [
            'currency'               => $currency,
            'remaining_for_customer' => (float)$remainingForCustomer,
            'remaining_for_company'  => (float)$remainingForCompany,
            'net_balance'            => (float)$net,
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

        // بطاقات الإجماليات من نفس المنطق القديم + المحفظة فقط
        $this->totalRemainingForCustomer = 0.0;
        $this->totalRemainingForCompany  = 0.0;

        $idsForTotals = $filteredIds ?: $all->pluck('id');
        foreach ($idsForTotals as $id) {
            $st = $statsById[$id] ?? null;
            if (!$st) continue;
            $this->totalRemainingForCustomer += $st['remaining_for_customer']; // عليه
            $this->totalRemainingForCompany  += $st['remaining_for_company'];  // له
        }

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
