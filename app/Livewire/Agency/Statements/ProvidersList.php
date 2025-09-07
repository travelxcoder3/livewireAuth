<?php

namespace App\Livewire\Agency\Statements;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\ProviderPayment; // غيّر الاسم إن كان مختلفًا
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class ProvidersList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true, keep: true)]     public string $search = '';
    #[Url(as: 'type', history: true, keep: true)]  public string $accountType = ''; // '', debit, credit
    #[Url(as: 'from', history: true, keep: true)]  public string $fromDate = '';
    #[Url(as: 'to',   history: true, keep: true)]  public string $toDate   = '';

    public float $totalRemainingForProvider = 0.0; // له
    public float $totalRemainingForAgency   = 0.0; // عليه

    public array $accountTypeOptions = [
        'debit'  => 'مدين (على المزود)',
        'credit' => 'دائن (لصالح المزود)',
    ];

    public function updatingSearch(){ $this->resetPage(); }
    public function updatingAccountType(){ $this->resetPage(); }
    public function updatingFromDate(){ $this->resetPage(); }
    public function updatingToDate(){ $this->resetPage(); }

    private function normalize(string $s): string
    {
        $s = str_replace('ـ','', trim($s));
        $map = ['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''];
        return mb_strtolower(strtr($s, $map));
    }

    private function baseQuery()
    {
        $term = $this->normalize($this->search);
        return Provider::where('agency_id', Auth::user()->agency_id)
            ->when($term !== '', function($q) use($term){
                $sql = "LOWER(name)";
                foreach (['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''] as $f=>$t) {
                    $sql = "REPLACE($sql,'$f','$t')";
                }
                $q->whereRaw("$sql LIKE ?", ['%'.$term.'%']);
            })
            ->orderByDesc('id');
    }


    private function buildStats(Provider $p): array
{
    $currency = Auth::user()->agency->currency ?? 'SAR';

    // إجلب الحقول التي قد تحمل التكلفة بأي اسم
    $sales = \App\Models\Sale::where('agency_id', Auth::user()->agency_id)
        ->where('provider_id', $p->id)
        ->get(['usd_buy','sar_buy','buy_price','provider_cost','sale_date','created_at','id']);

    // آخر عملية
    $last = $sales->sortByDesc(fn($s)=>[$s->sale_date ?? $s->created_at, $s->id])->first();
    $lastDate = $last?->sale_date ?? $last?->created_at;

    // مجموع تكلفة المزود = له
    $totalCost = (float)$sales->sum(function($s){
        return (float)($s->usd_buy ?? $s->sar_buy ?? $s->buy_price ?? $s->provider_cost ?? 0);
    });

    // مدفوعات للمزوّد = عليه (على الوكالة)
    $paid = (float)ProviderPayment::where('agency_id', Auth::user()->agency_id)
        ->where('provider_id', $p->id)
        ->sum('amount');

    // صافي المستحق للمزوّد = التكلفة - المدفوع
    $net = round($totalCost - $paid, 2);

    return [
        'currency'               => $currency,
        'remaining_for_provider' => max($net, 0),   // له
        'remaining_for_agency'   => max(-$net, 0),  // عليه
        'net_balance'            => $net,           // موجب = دائن، سالب = مدين
        'last_sale_date'         => $lastDate ? \Carbon\Carbon::parse($lastDate)->format('Y-m-d') : '-',
    ];
}



    private function passDateFilter(?string $lastSaleDate): bool
    {
        if (!$this->fromDate && !$this->toDate) return true;
        if (!$lastSaleDate || $lastSaleDate === '-') return false;

        $d = \Carbon\Carbon::parse($lastSaleDate)->startOfDay();
        $from = $this->fromDate ? \Carbon\Carbon::parse($this->fromDate)->startOfDay() : null;
        $to   = $this->toDate   ? \Carbon\Carbon::parse($this->toDate)->endOfDay()   : null;

        if ($from && $d->lt($from)) return false;
        if ($to   && $d->gt($to))   return false;
        return true;
    }

    public function resetFilters(): void
    {
        $this->search = $this->accountType = $this->fromDate = $this->toDate = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->baseQuery();
        $all   = (clone $query)->get();

        $stats = [];
        foreach ($all as $p) $stats[$p->id] = $this->buildStats($p);

        $filteredIds = collect($all)->filter(function($p) use ($stats){
            $st = $stats[$p->id] ?? null; if(!$st) return false;
            if (!$this->passDateFilter($st['last_sale_date'])) return false;
            if ($this->accountType === 'debit'  && $st['net_balance'] <= 0) return false;
            if ($this->accountType === 'credit' && $st['net_balance'] >= 0) return false;
            return true;
        })->pluck('id')->all();

        if ($this->accountType !== '' || $this->fromDate || $this->toDate) {
            $query->whereIn('id', $filteredIds ?: [-1]);
        }

        $this->totalRemainingForProvider = 0.0;
        $this->totalRemainingForAgency   = 0.0;

        $idsForTotals = $filteredIds ?: $all->pluck('id');
        foreach ($idsForTotals as $id) {
            $st = $stats[$id] ?? null; if(!$st) continue;
            $this->totalRemainingForProvider += $st['remaining_for_provider'];
            $this->totalRemainingForAgency   += $st['remaining_for_agency'];
        }

            $employees = $query->paginate(10)->through(function($p) use ($stats){
            $st = $stats[$p->id];
            $p->details_url       = route('agency.statements.provider', $p->id);
            $p->currency          = $st['currency'];
            $p->last_sale_date    = $st['last_sale_date'];
            $p->balance_total     = $st['net_balance'];
            $p->account_type_text = $st['net_balance'] >= 0 ? 'دائن' : 'مدين';
            return $p;
        });



        return view('livewire.agency.statements.providers-list', [
            'employees'                => $employees, // اسم المتغير حر
            'accountTypeOptions'       => $this->accountTypeOptions,
            'totalRemainingForEmployee'=> $this->totalRemainingForProvider,
            'totalRemainingForCompany' => $this->totalRemainingForAgency,
        ]);
    }
}
