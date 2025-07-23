<?php

namespace App\Livewire\HR;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DynamicListItem;



use Livewire\Attributes\Layout;

#[Layout('layouts.agency')]
class EmployeeIndex extends Component
{
    use WithPagination;
    protected $listeners = ['refreshEmployeeList' => 'refreshEmployees'];

    public $search = '';
    public $department_id = '';
    public $position_id = '';
    public $showForm = false;

    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $phone = '';
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
        $this->departments = DynamicListItem::whereHas('list', function ($query) {
            $query->where('name', 'قائمة الاقسام');
        })->pluck('label', 'id')->toArray();

        $this->positions = DynamicListItem::whereHas('list', function ($query) {
            $query->where('name', 'قائمة المسمى الوظيفي');
        })->pluck('label', 'id')->toArray();


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
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'department_id', 'position_id', 'phone', 'user_name', 'editingEmployee']);
        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->editingEmployee = null;

        $this->reset([
            'name',
            'email',
            'password',
            'password_confirmation',
            'phone',
            'user_name',
            'search',
            'department_id',
            'position_id'
        ]);
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
            'department_id' => 'required|exists:dynamic_list_items,id',
            'position_id' => 'required|exists:dynamic_list_items,id',
            'phone' => 'nullable|string|max:20',
            'user_name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',



        ]);

        $employee = \App\Models\User::findOrFail($this->editingEmployee);
        $employee->name = $this->name;
        $employee->email = $this->email;
        $employee->department_id = $this->department_id;
        $employee->position_id = $this->position_id;
        $employee->phone = $this->phone;
        $employee->user_name = $this->user_name;
        if ($this->password) {
            $employee->password = bcrypt($this->password);
        }


        $employee->save();

        session()->flash('success', 'تم تحديث بيانات الموظف بنجاح.');
        $this->closeForm();
        $this->reset(['search', 'department_id', 'position_id']); // إعادة تعيين الفلاتر
        $this->resetPage(); // إعادة تعيين التقسيم
    }

    public function addEmployee()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'department_id' => 'required|exists:dynamic_list_items,id',
            'position_id' => 'required|exists:dynamic_list_items,id',
            'phone' => 'nullable|string|max:20',
            'user_name' => 'nullable|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = new \App\Models\User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->department_id = $this->department_id;
        $user->position_id = $this->position_id;
        $user->phone = $this->phone;
        $user->user_name = $this->user_name;
        $user->password = bcrypt($this->password);
        $user->is_active = false;
        $user->agency_id = Auth::user()->agency_id;

        $user->save();

        session()->flash('success', 'تم إضافة الموظف بنجاح.');

        // إعادة تعيين البحث والفلاتر
        $this->reset(['search', 'department_id', 'position_id']);
        $this->resetPage(); // لإعادة تعيين الصفحة إذا كنت تستخدم التقسيم

        $this->closeForm();
    }

    public function refreshEmployees()
    {
        $this->resetPage();
        $this->render();
    }
    public function resetFilters()
    {
        $this->search = '';
        $this->department_id = '';
        $this->position_id = '';
        $this->resetPage(); // لإعادة تعيين الصفحة في حالة استخدام pagination
    }

    public function render()
    {
        $agency = Auth::user()->agency;
        if ($agency && $agency->parent_id === null) {
            $agencyIds = $agency->branches()->pluck('id')->toArray();
            $agencyIds[] = $agency->id;
        } else {
            $agencyIds = [$agency->id];
        }

        $query = User::with(['department', 'position'])
            ->whereIn('agency_id', $agencyIds);

        // تطبيق الفلاتر فقط إذا كانت لها قيمة
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                    ->orWhere('email', 'LIKE', $searchTerm);
            });
        }

        if (!empty($this->department_id)) {
            $query->where('department_id', $this->department_id);
        }

        if (!empty($this->position_id)) {
            $query->where('position_id', $this->position_id);
        }

        $employees = $query->latest()->paginate(10);

        return view('livewire.hr.employee-index', compact('employees'));
    }
}
