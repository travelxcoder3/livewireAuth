<?php

namespace App\Livewire\Agency\Statements;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\EmployeeWallet;
use App\Models\EmployeeWalletTransaction;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class EmployeesList extends Component
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

    public float $totalRemainingForEmployee = 0.0; // له
    public float $totalRemainingForCompany  = 0.0; // عليه

    public array $accountTypeOptions = [
        'debit'  => 'مدين (على الموظف)',
        'credit' => 'دائن (لصالح الموظف)',
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

    private function baseQuery()
    {
        $term = $this->normalize($this->search);

        return User::where('agency_id', Auth::user()->agency_id)
            ->when($term !== '', function($q) use($term){
                $sql1 = "LOWER(name)"; $sql2 = "LOWER(user_name)";
                foreach (['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''] as $f=>$t) {
                    $sql1 = "REPLACE($sql1,'$f','$t')";
                    $sql2 = "REPLACE($sql2,'$f','$t')";
                }
                $q->where(function($qq) use ($sql1,$sql2,$term){
                    $qq->whereRaw("$sql1 LIKE ?", ['%'.$term.'%'])
                       ->orWhereRaw("$sql2 LIKE ?", ['%'.$term.'%']);
                });
            })
            ->orderByDesc('id');
    }

    private function buildStats(User $u): array
    {
        $currency = Auth::user()->agency->currency ?? 'USD';

        $wallet = EmployeeWallet::firstOrCreate(
            ['user_id' => $u->id],
            ['balance' => 0, 'status' => 'active']
        );

        // الرصيد الحالي من جدول المحفظة مباشرة
        $balance = (float)($wallet->balance ?? 0);

        // آخر حركة لها معنى زمني في الكشف
        $lastTxnAt = EmployeeWalletTransaction::where('wallet_id', $wallet->id)
            ->max('created_at');
        $lastDate = $lastTxnAt ? \Carbon\Carbon::parse($lastTxnAt)->format('Y-m-d') : null;

        // له/عليه مشتقة من الرصيد
        $remainingForEmployee = $balance > 0 ? $balance : 0.0;     // له
        $remainingForCompany  = $balance < 0 ? abs($balance) : 0.0; // عليه

        $net = $remainingForEmployee - $remainingForCompany; // موجب = دائن للموظف

        return [
            'currency'               => $currency,
            'remaining_for_employee' => $remainingForEmployee,
            'remaining_for_company'  => $remainingForCompany,
            'net_balance'            => $net,
            'last_tx_date'           => $lastDate,
        ];
    }

    private function passDateFilter(?string $lastDate): bool
    {
        if (!$this->fromDate && !$this->toDate) return true;
        if (!$lastDate) return false;

        $d = \Carbon\Carbon::parse($lastDate)->startOfDay();
        $from = $this->fromDate ? \Carbon\Carbon::parse($this->fromDate)->startOfDay() : null;
        $to   = $this->toDate   ? \Carbon\Carbon::parse($this->toDate)->endOfDay()   : null;

        if ($from && $d->lt($from)) return false;
        if ($to   && $d->gt($to))   return false;
        return true;
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

        $statsById = [];
        foreach ($all as $u) $statsById[$u->id] = $this->buildStats($u);

        // فلترة حسب التاريخ ونوع الحساب على صافي الرصيد
        $filteredIds = collect($all)->filter(function($u) use ($statsById){
            $st = $statsById[$u->id] ?? null;
            if (!$st) return false;
            if (!$this->passDateFilter($st['last_tx_date'])) return false;
            if ($this->accountType === 'debit'  && $st['net_balance'] >= 0) return false; // مدين = عليه
            if ($this->accountType === 'credit' && $st['net_balance'] <= 0) return false; // دائن = له
            return true;
        })->pluck('id')->all();

        if ($this->accountType !== '' || $this->fromDate || $this->toDate) {
            $query->whereIn('id', $filteredIds ?: [-1]);
        }

        // البطاقات
        $this->totalRemainingForEmployee = 0.0; // له
        $this->totalRemainingForCompany  = 0.0; // عليه

        $idsForTotals = $filteredIds ?: $all->pluck('id');
        foreach ($idsForTotals as $id) {
            $st = $statsById[$id] ?? null;
            if (!$st) continue;
            $this->totalRemainingForEmployee += $st['remaining_for_employee'];
            $this->totalRemainingForCompany  += $st['remaining_for_company'];
        }

        // الجدول
        $employees = $query->paginate(10)->through(function ($u) use ($statsById) {
            $st = $statsById[$u->id] ?? [];

            return (object) [
                'id'               => $u->id,
                'name'             => $u->name ?? $u->user_name,
               'account_type_text'=> $st['net_balance'] > 0 
                                        ? 'دائن' 
                                        : ($st['net_balance'] < 0 ? 'مدين' : 'متزن'),
                'balance_total'    => abs($st['net_balance'] ?? 0),
                'currency'         => $st['currency'] ?? 'USD',
                'last_sale_date'   => $st['last_sale_date'] ?? '-',   // <== هنا نضمن القيمة
                'details_url'      => route('agency.statements.employee', $u->id),
            ];
        });



        return view('livewire.agency.statements.employees-list', [
            'employees'                 => $employees,
            'accountTypeOptions'        => $this->accountTypeOptions,
            'totalRemainingForEmployee' => $this->totalRemainingForEmployee,
            'totalRemainingForCompany'  => $this->totalRemainingForCompany,
        ]);
    }
}
