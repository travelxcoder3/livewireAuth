<?php

namespace App\Livewire\HR;

use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

use Livewire\Attributes\Layout;

#[Layout('layouts.agency')]
class EmployeeEdit extends Component
{
    public User $employee;
    public $name, $user_name, $email, $phone, $branch;
    public $department_id, $position_id;
    public $departments = [], $positions = [];

    public function mount($employee)
    {
        $this->employee = User::findOrFail($employee);

        if ($this->employee->agency_id !== Auth::user()->agency_id) {
            abort(403);
        }

        $this->departments = Department::pluck('name', 'id')->toArray();
        $this->positions = Position::pluck('name', 'id')->toArray();

        $this->name = $this->employee->name;
        $this->user_name = $this->employee->user_name;
        $this->email = $this->employee->email;
        $this->phone = $this->employee->phone;
        $this->branch = $this->employee->branch;
        $this->department_id = $this->employee->department_id;
        $this->position_id = $this->employee->position_id;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'user_name' => 'required|string|max:255|unique:users,user_name,' . $this->employee->id,
            'email' => 'required|email|unique:users,email,' . $this->employee->id,
            'phone' => 'nullable|string|max:20',
            'branch' => 'nullable|string|max:100',
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
        ]);

        $this->employee->update([
            'name' => $this->name,
            'user_name' => $this->user_name,
            'is_active' => false,
            'email' => $this->email,
            'phone' => $this->phone,
            'branch' => $this->branch,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
        ]);

        session()->flash('success', 'تم تحديث بيانات الموظف بنجاح');
        // إطلاق الحدث لتحديث EmployeeIndex
        $this->dispatch('refreshEmployeeList'); 
        return redirect()->route('agency.hr.employees.index');
    }

    public function render()
    {
        return view('livewire.hr.employee-edit');
    }
}
