<?php

namespace App\Livewire\Agency\Obligations;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Obligation;

class Index extends Component
{
    use WithPagination;

    public $agencyId;
    public $title;
    public $description;
    public $start_date;
    public $end_date;
    public $for_all = false;
    public $selectedObligation = null;
    public $search = '';
    public $showModal = false;
    public $selectedUsers = [];
    public $employees = [];

    protected $rules = [
        'title'           => 'required|string|max:255',
        'description'     => 'nullable|string',
        'start_date'      => 'nullable|date',
        'end_date'        => 'nullable|date|after_or_equal:start_date',
        'for_all'         => 'boolean',
        'selectedUsers'   => 'required_if:for_all,false|array',
        'selectedUsers.*' => 'exists:users,id',
    ];

    public function mount()
    {
        $this->agencyId  = Auth::user()->agency_id;
        $this->loadEmployees();
    }

    public function loadEmployees()
    {
        $this->employees = User::where('agency_id', $this->agencyId)->get();
    }

    // عند الضغط على "تحديد الكل" أو "إلغاء التحديد"
    public function toggleSelectAll()
    {
        if (count($this->selectedUsers) < $this->employees->count()) {
            $this->selectedUsers = $this->employees->pluck('id')->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(Obligation $obligation)
    {
        $this->selectedObligation = $obligation;
        $this->title       = $obligation->title;
        $this->description = $obligation->description;
        $this->start_date  = $obligation->start_date;
        $this->end_date    = $obligation->end_date;
        $this->for_all     = $obligation->for_all;
        $this->selectedUsers = $obligation->users->pluck('id')->toArray();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'agency_id'   => $this->agencyId,
            'title'       => $this->title,
            'description' => $this->description,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date,
            'for_all'     => $this->for_all,
        ];

        if ($this->selectedObligation) {
            $this->selectedObligation->update($data);
            $ob = $this->selectedObligation;
        } else {
            $ob = Obligation::create($data);
        }

        if (! $this->for_all) {
            $ob->users()->sync($this->selectedUsers);
        } else {
            $ob->users()->detach();
        }

        session()->flash('message', 'تم حفظ الالتزام.');
        $this->resetForm();
        $this->showModal = false;
          $this->resetPage(); 
    }

    public function delete(Obligation $obligation)
    {
        $obligation->delete();
        session()->flash('message', 'تم حذف الالتزام.');
          $this->resetPage(); 
    }

    private function resetForm()
    {
        $this->selectedObligation = null;
        $this->title              = '';
        $this->description        = '';
        $this->start_date         = null;
        $this->end_date           = null;
        $this->for_all            = false;
        $this->selectedUsers      = [];
    }

   public function render()
{
    $obligations = Obligation::with('users')
        ->where('agency_id', $this->agencyId)
        ->when($this->search, fn($q)=> $q->where('title','like',"%{$this->search}%"))
        ->orderBy('created_at','desc')
        ->paginate(10);

    return view('livewire.agency.obligations.index', compact('obligations'))
           ->layout('layouts.agency');
}

}
