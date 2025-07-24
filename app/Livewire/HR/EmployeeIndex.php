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

    protected $listeners = ['refreshEmployeeList' => '$refresh'];

    // فلترة البحث (للواجهة الرئيسية فقط)
    public $search = '';
    public $department_filter = '';
    public $position_filter = '';

    // نموذج الإضافة/التعديل
    public $showForm = false;
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $phone = '';
    public $user_name = '';
    public $editingEmployee = null;
    public $form_department_id = ''; // خاص بالنموذج
    public $form_position_id = '';   // خاص بالنموذج

    // القوائم المنسدلة
    public $departments = [];
    public $positions = [];

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

    // تطبيق الفلاتر عند التغيير
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter()
    {
        $this->resetPage();
    }

    public function updatedPositionFilter()
    {
        $this->resetPage();
    }

    // فتح نموذج إضافة موظف جديد
    public function createEmployee()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    // إغلاق النموذج
    public function closeForm()
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset([
            'name',
            'email',
            'password',
            'password_confirmation',
            'phone',
            'user_name',
            'form_department_id',
            'form_position_id',
            'editingEmployee'
        ]);
    }

    // فتح نموذج تعديل موظف
    public function editEmployee($id)
    {
        $employee = User::findOrFail($id);
        $this->editingEmployee = $employee->id;
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->form_department_id = $employee->department_id; // استخدام متغير النموذج
        $this->form_position_id = $employee->position_id;     // استخدام متغير النموذج
        $this->phone = $employee->phone;
        $this->user_name = $employee->user_name;
        $this->showForm = true;
    }

    // تحديث بيانات الموظف
    public function updateEmployee()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->editingEmployee,
            'form_department_id' => 'required|exists:dynamic_list_items,id',
            'form_position_id' => 'required|exists:dynamic_list_items,id',
            'phone' => 'nullable|string|max:20',
            'user_name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'department_id' => $this->form_department_id,
            'position_id' => $this->form_position_id,
            'phone' => $this->phone,
            'user_name' => $this->user_name,
        ];

        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        User::find($this->editingEmployee)->update($data);

        session()->flash('success', 'تم تحديث بيانات الموظف بنجاح.');
        $this->closeForm();
        $this->dispatch('refreshEmployeeList');
    }

    // إضافة موظف جديد
    public function addEmployee()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'form_department_id' => 'required|exists:dynamic_list_items,id',
            'form_position_id' => 'required|exists:dynamic_list_items,id',
            'phone' => 'nullable|string|max:20',
            'user_name' => 'nullable|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'department_id' => $this->form_department_id,
            'position_id' => $this->form_position_id,
            'phone' => $this->phone,
            'user_name' => $this->user_name,
            'password' => bcrypt($this->password),
            'is_active' => false,
            'agency_id' => Auth::user()->agency_id,
        ]);

        session()->flash('success', 'تم إضافة الموظف بنجاح.');
        $this->closeForm();
        $this->dispatch('refreshEmployeeList');
    }

    // إعادة تعيين الفلاتر
    public function resetFilters()
    {
        $this->reset(['search', 'department_filter', 'position_filter']);
        $this->resetPage();
    }

    // عرض الموظفين مع تطبيق الفلاتر
    public function render()
    {
        $agency = Auth::user()->agency;
        $agencyIds = $agency->parent_id === null
            ? array_merge([$agency->id], $agency->branches()->pluck('id')->toArray())
            : [$agency->id];

        $employees = User::with(['department', 'position'])
            ->whereIn('agency_id', $agencyIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('email', 'LIKE', '%' . $this->search . '%');
                });
            })
            ->when($this->department_filter, fn($q) => $q->where('department_id', $this->department_filter))
            ->when($this->position_filter, fn($q) => $q->where('position_id', $this->position_filter))
            ->latest()
            ->paginate(10);

        return view('livewire.hr.employee-index', [
            'employees' => $employees,
            'departments' => $this->departments,
            'positions' => $this->positions
        ]);
    }
}
