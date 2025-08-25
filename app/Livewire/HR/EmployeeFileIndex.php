<?php

namespace App\Livewire\HR;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DynamicListItem;
use Livewire\Attributes\Layout;

#[Layout('layouts.agency')]
class EmployeeFileIndex extends Component
{
    use WithPagination;

    protected $listeners = [
        'refreshEmployeeList' => '$refresh',
        'closeEmployeeWalletFromParent' => 'closeWallet',
    ];

    // فلاتر
    public $search = '';
    public $department_filter = '';
    public $position_filter = '';

    // قوائم
    public $departments = [];
    public $positions   = [];

    // محفظة
    public bool $showWallet = false;
    public ?int $walletUserId = null;

    public function mount()
    {
        $agencyId = auth()->user()->agency_id;

        $this->departments = DynamicListItem::whereHas('list', fn($q) => $q->where('name','قائمة الاقسام'))
            ->where(fn($q)=>$q->where('created_by_agency',$agencyId)->orWhereNull('created_by_agency'))
            ->pluck('label','id')->toArray();

        $this->positions = DynamicListItem::whereHas('list', fn($q) => $q->where('name','قائمة المسمى الوظيفي'))
            ->where(fn($q)=>$q->where('created_by_agency',$agencyId)->orWhereNull('created_by_agency'))
            ->pluck('label','id')->toArray();
    }

    // تحديث الفلاتر يعيد للصفحة الأولى
    public function updatedSearch()          { $this->resetPage(); }
    public function updatedDepartmentFilter(){ $this->resetPage(); }
    public function updatedPositionFilter()  { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search','department_filter','position_filter']);
        $this->resetPage();
    }

    public function openWallet($id)
    {
        $this->walletUserId = (int)$id;
        $this->showWallet   = true;
    }

    public function closeWallet()
    {
        $this->showWallet   = false;
        $this->walletUserId = null;
    }

    public function render()
    {
        $agency = Auth::user()->agency;
        $agencyIds = $agency->parent_id === null
            ? array_merge([$agency->id], $agency->branches()->pluck('id')->toArray())
            : [$agency->id];

        $employees = User::with(['department','position'])
            ->whereIn('agency_id',$agencyIds)
            ->when($this->search, function ($q) {
                $q->where(fn($qq)=>$qq->where('name','like',"%{$this->search}%")
                                      ->orWhere('email','like',"%{$this->search}%"));
            })
            ->when($this->department_filter, fn($q)=>$q->where('department_id',$this->department_filter))
            ->when($this->position_filter,   fn($q)=>$q->where('position_id',$this->position_filter))
            ->oldest()
            ->paginate(10);

        return view('livewire.hr.employee-file-index', [
            'employees'   => $employees,
            'departments' => $this->departments,
            'positions'   => $this->positions,
        ]);
    }
}
