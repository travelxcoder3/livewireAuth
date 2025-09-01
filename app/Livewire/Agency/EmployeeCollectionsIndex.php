<?php
namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\{Sale, Collection, User};

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
            $remaining = $empSales->groupBy(fn($s)=>$s->sale_group_id ?? $s->id)
                ->sum(function($group){
                    $total = $group->sum('usd_sell');
                    $paid  = $group->sum('amount_paid');
                    $coll  = $group->flatMap->collections->sum('amount');
                    $rem   = $total - $paid - $coll;
                    return $rem > 0 ? $rem : 0;
                });

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

}
