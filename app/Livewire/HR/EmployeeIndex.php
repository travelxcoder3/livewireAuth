<?php

namespace App\Livewire\HR;

use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;



use Livewire\Attributes\Layout;

#[Layout('layouts.agency')]
class EmployeeIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $department_id = '';
    public $position_id = '';
    public $showForm = false;

    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $phone = '';
    public $branch = '';
    public $user_name = '';
    public $editingEmployee = null;

    public $departments = [];
    public $positions = [];
    protected $updatesQueryString = ['search', 'department_id', 'position_id'];

    protected $queryString = ['search'];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->departments = Department::pluck('name', 'id')->toArray();
        $this->positions = Position::pluck('name', 'id')->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDepartmentId()
    {
        $this->resetPage();
    }

    public function updatingPositionId()
    {
        $this->resetPage();
    }

    public function createEmployee()
    {
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'department_id', 'position_id', 'phone', 'branch', 'user_name', 'editingEmployee']);
        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->editingEmployee = null;
    }

    public function editEmployee($id)
    {
        $employee = \App\Models\User::findOrFail($id);
        $this->editingEmployee = $employee->id;
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->department_id = $employee->department_id;
        $this->position_id = $employee->position_id;
        $this->phone = $employee->phone;
        $this->branch = $employee->branch;
        $this->user_name = $employee->user_name;
        $this->showForm = true;
    }

    public function updateEmployee()
    {
        // تحقق من وجود موظف للتعديل
        if (!$this->editingEmployee) {
            session()->flash('error', 'لا يوجد موظف محدد للتعديل.');
            return;
        }

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->editingEmployee,
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'phone' => 'nullable|string|max:20',
            'branch' => 'nullable|string|max:255',
            'user_name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $employee = \App\Models\User::findOrFail($this->editingEmployee);
        $employee->name = $this->name;
        $employee->email = $this->email;
        $employee->department_id = $this->department_id;
        $employee->position_id = $this->position_id;
        $employee->phone = $this->phone;
        $employee->branch = $this->branch;
        $employee->user_name = $this->user_name;
        if ($this->password) {
            $employee->password = bcrypt($this->password);
        }
        $employee->save();

        session()->flash('success', 'تم تحديث بيانات الموظف بنجاح.');
        $this->closeForm();
        $this->dispatch('$refresh');
    }

    public function addEmployee()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'phone' => 'nullable|string|max:20',
            'branch' => 'nullable|string|max:255',
            'user_name' => 'nullable|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = new \App\Models\User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->department_id = $this->department_id;
        $user->position_id = $this->position_id;
        $user->phone = $this->phone;
        $user->branch = $this->branch;
        $user->user_name = $this->user_name;
        $user->password = bcrypt($this->password);
        $user->agency_id = Auth::user()->agency_id;
        $user->save();

        session()->flash('success', 'تم إضافة الموظف بنجاح.');
        $this->closeForm();
        $this->dispatch('$refresh');
    }

    public function refreshEmployees()
    {
        // إعادة تحميل البيانات
        $this->render();
    }

    public function render()
    {
        $employees = User::with(['department', 'position'])
            ->where('agency_id', Auth::user()->agency_id)
            ->when($this->search, function ($query) {
                $searchTerm = '%' . $this->search . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', $searchTerm)
                        ->orWhere('email', 'LIKE', $searchTerm);
                });
            })
            ->when($this->department_id, fn($q) => $q->where('department_id', $this->department_id))
            ->when($this->position_id, fn($q) => $q->where('position_id', $this->position_id))
            ->latest()
            ->paginate(10);

        return view('livewire.hr.employee-index', compact('employees'));
    }
}
