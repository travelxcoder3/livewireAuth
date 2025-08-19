<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\EmployeeWallet as EmployeeWalletModel;
use App\Models\EmployeeWalletTransaction;
use App\Models\Sale;
use App\Models\CommissionProfile;
use Carbon\Carbon;
use App\Models\Collection;

class EmployeeWallet extends Component
{
    use WithPagination;

    public int $userId;
    public ?User $user = null;
    public ?EmployeeWalletModel $wallet = null;

    public $type = 'deposit';
    public $amount;
    public $reference;
    public $note;

    public $from;
    public $to;
    public $q = '';

    protected $listeners = ['wallet-closed' => 'close'];

    public function mount(int $userId)
    {
        $this->userId = $userId;
        $this->user   = User::findOrFail($userId);
        $this->wallet = EmployeeWalletModel::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'status' => 'active']
        );

    }

   public function submit()
{
    $this->validate([
        'type' => 'required|in:deposit,withdraw,adjust',
        'amount' => 'required|numeric|min:0.01',
        'reference' => 'nullable|string|max:255',
        'note' => 'nullable|string|max:2000',
    ]);

    $svc = app(\App\Services\EmployeeWalletService::class);
    $wallet = $svc->ensureWallet($this->userId);

    if ($this->type === 'deposit') {
        $svc->post($wallet, 'deposit', (float)$this->amount, $this->reference, $this->note, auth()->user()->name ?? 'system');
    } elseif ($this->type === 'withdraw') {
        $svc->post($wallet, 'withdraw', (float)$this->amount, $this->reference, $this->note, auth()->user()->name ?? 'system');
    } else { // adjust = ضبط رصيد نهائي
        // نضبط الرصيد بعملية adjust (يُسجّل الفرق تلقائيًا في الخدمة)
        // هنا نستخدم post مباشرة بقيمة الرصيد النهائي = amount
        // للتبسيط: نجعل type=adjust ويُفهم في الواجهة أنه رصيد نهائي
        $svc->post($wallet, 'adjust', (float)$this->amount, $this->reference, $this->note, auth()->user()->name ?? 'system');
    }

    $this->wallet->refresh();
    $this->reset(['type','amount','reference','note']);
    $this->type = 'deposit';
    session()->flash('message','تم تنفيذ العملية');
}


    public function getTransactionsProperty()
    {
        return EmployeeWalletTransaction::where('wallet_id', $this->wallet->id)
            ->when($this->from, fn($q)=>$q->whereDate('created_at','>=',$this->from))
            ->when($this->to,   fn($q)=>$q->whereDate('created_at','<=',$this->to))
            ->when($this->q, function($q){
                $q->where(function($qq){
                    $qq->where('reference','like',"%{$this->q}%")
                       ->orWhere('note','like',"%{$this->q}%")
                       ->orWhere('performed_by_name','like',"%{$this->q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function updatingFrom(){ $this->resetPage(); }
    public function updatingTo(){ $this->resetPage(); }
    public function updatingQ(){ $this->resetPage(); }

    public function close(){ $this->dispatch('closeEmployeeWalletFromParent'); }

    public function render()
    {
        return view('livewire.agency.employee-wallet', [
            'currentOverdueDebt' => $this->currentOverdueDebt,
        ]);
    }

    public function getCurrentOverdueDebtProperty(): float
    {
        // سياسة المهلة
        $profile = CommissionProfile::where('agency_id', $this->user->agency_id)
            ->where('is_active', true)->first();

        $days = (int)($profile->days_to_debt ?? 0);
        if ($days <= 0) return 0.0;

        // أحدث سداد "عام" لأي عميل يتبع هذا الموظف
        $latestAnyPay = Collection::whereHas('sale', function ($q) {
                $q->where('agency_id', $this->user->agency_id)
                ->where('user_id',   $this->userId)
                ->where('status','!=','Void');
            })
            ->max('payment_date');

        // إذا يوجد سداد خلال المهلة => لا يوجد دين حالي
        if ($latestAnyPay && Carbon::parse($latestAnyPay)->addDays($days)->isFuture()) {
            return 0.0;
        }

        // لا يوجد سداد ضمن المهلة -> احسب إجمالي المتبقي على العملاء (غير المحصل بالكامل)
        $sales = Sale::withSum('collections','amount')
            ->where('agency_id', $this->user->agency_id)
            ->where('user_id',   $this->userId)
            ->where('status','!=','Void')
            ->get(['id','sale_group_id','usd_sell','amount_paid','sale_date']);

        // اجمع المتبقي لكل مجموعة بيع
        $groups = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

        $total = 0.0;
        foreach ($groups as $g) {
            $required  = (float)$g->sum('usd_sell');
            $collected = (float)$g->sum('amount_paid') + (float)$g->sum('collections_sum_amount');
            $rem = $required - $collected;
            if ($rem > 0) $total += $rem;
        }

        return round($total, 2);
    }



}
