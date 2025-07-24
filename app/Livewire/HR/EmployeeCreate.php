<?php

namespace App\Livewire\HR;

use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.agency')]
class EmployeeCreate extends Component
{
    public $name, $user_name, $email, $password, $password_confirmation;
    public $department_id, $position_id, $phone, $branch;
    public $departments = [], $positions = [];

    public function mount()
    {
        $agencyId = auth()->user()->agency_id;
        
        $this->departments = DynamicListItem::whereHas('list', function ($query) {
            $query->where('name', 'قائمة الاقسام');
        })
        ->where(function($query) use ($agencyId) {
            $query->where('created_by_agency', $agencyId)
                  ->orWhereNull('created_by_agency');
        })
        ->pluck('label', 'id')
        ->toArray();

        $this->positions = DynamicListItem::whereHas('list', function ($query) {
            $query->where('name', 'قائمة المسمى الوظيفي');
        })
        ->where(function($query) use ($agencyId) {
            $query->where('created_by_agency', $agencyId)
                  ->orWhereNull('created_by_agency');
        })
        ->pluck('label', 'id')
        ->toArray();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|min:3',
            'user_name' => 'required|string|min:3|unique:users,user_name',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'phone' => 'nullable|string|max:20',
            'branch' => 'nullable|string|max:100',
        ];
    }

    public function save()
    {
        $this->validate();

        User::create([
            'name' => $this->name,
            'user_name' => $this->user_name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'phone' => $this->phone,
            'branch' => $this->branch,
            'agency_id' => Auth::user()->agency_id,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'is_active' => false,
        ]);

        session()->flash('success', 'تمت إضافة الموظف بنجاح');
        $this->dispatch('refreshEmployeeList');
        return redirect()->route('agency.hr.employees.index');
    }

    public function render()
    {
        return view('livewire.hr.employee-create');
    }
}
