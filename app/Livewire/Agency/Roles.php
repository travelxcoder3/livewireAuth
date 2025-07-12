<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class Roles extends Component
{
    use WithPagination;
    public $showPermissionsModal = false;
    public $selectedRolePermissions = [];
    public $selectedRoleName = '';

    public $showForm = false;
    public $description;
    public $permissions = [];
    public $availablePermissions = []; // يجب تمرير الصلاحيات من الكومبوننت

    public $showAddModal = false;
    public $showEditModal = false;
    public $editingRole = null;

    // حقول إضافة دور جديد
    public $name = '';
    public $selectedPermissions = [];
    public $showEditPermissions = false;

    // حقول تعديل الدور
    public $edit_name = '';
    public $edit_selectedPermissions = [];

    public $showPermissions = false;
    public $openModules = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'selectedPermissions' => 'required|array|min:1',
        'selectedPermissions.*' => 'exists:permissions,name',
    ];

    public function mount()
    {
        $this->loadPermissions();
        $this->showForm = false;
        $this->showPermissions = false;
        $this->openModules = [];
    }

    public function showRolePermissions($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->selectedRolePermissions = $role->permissions->pluck('name')->toArray();
        $this->selectedRoleName = $role->display_name ?? $role->name;
        $this->showPermissionsModal = true;
    }

    public function loadPermissions()
    {
        // جلب الصلاحيات العامة لجميع الوكالات
        $this->permissions = Permission::whereNull('agency_id')->get();
    }

    // دالة اختيار جميع الصلاحيات
    public function selectAllPermissions()
    {
        $this->selectedPermissions = $this->permissions->pluck('name')->toArray();
        session()->flash('message', 'تم اختيار جميع الصلاحيات (' . count($this->selectedPermissions) . ' صلاحية)');
    }

    // دالة إلغاء اختيار جميع الصلاحيات
    public function deselectAllPermissions()
    {
        $this->selectedPermissions = [];
        session()->flash('message', 'تم إلغاء اختيار جميع الصلاحيات');
    }

    // دالة اختيار جميع صلاحيات قسم معين
    public function selectAllModulePermissions($module)
    {
        $modulePermissions = $this->permissions->filter(function($permission) use ($module) {
            return str_starts_with($permission->name, $module . '.');
        })->pluck('name')->toArray();
        
        // إضافة الصلاحيات الجديدة دون تكرار
        $this->selectedPermissions = array_unique(array_merge($this->selectedPermissions, $modulePermissions));
        session()->flash('message', "تم اختيار جميع صلاحيات قسم {$module} (" . count($modulePermissions) . " صلاحية)");
    }

    // دالة إلغاء اختيار جميع صلاحيات قسم معين
    public function deselectAllModulePermissions($module)
    {
        $modulePermissions = $this->permissions->filter(function($permission) use ($module) {
            return str_starts_with($permission->name, $module . '.');
        })->pluck('name')->toArray();
        
        // إزالة صلاحيات القسم المحدد
        $this->selectedPermissions = array_diff($this->selectedPermissions, $modulePermissions);
        session()->flash('message', "تم إلغاء اختيار جميع صلاحيات قسم {$module} (" . count($modulePermissions) . " صلاحية)");
    }

    // دالة للتحقق من اختيار جميع صلاحيات قسم معين
    public function isModuleFullySelected($module)
    {
        $modulePermissions = $this->permissions->filter(function($permission) use ($module) {
            return str_starts_with($permission->name, $module . '.');
        })->pluck('name')->toArray();
        
        $selectedModulePermissions = array_intersect($this->selectedPermissions, $modulePermissions);
        
        return count($selectedModulePermissions) === count($modulePermissions) && count($modulePermissions) > 0;
    }

    // دالة للتحقق من اختيار بعض صلاحيات قسم معين
    public function isModulePartiallySelected($module)
    {
        $modulePermissions = $this->permissions->filter(function($permission) use ($module) {
            return str_starts_with($permission->name, $module . '.');
        })->pluck('name')->toArray();
        
        $selectedModulePermissions = array_intersect($this->selectedPermissions, $modulePermissions);
        
        return count($selectedModulePermissions) > 0 && count($selectedModulePermissions) < count($modulePermissions);
    }

    public function closeForm()
    {
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'name', 
            'selectedPermissions', 
            'editingRole',
            'showPermissions',
            'openModules'
        ]);
        $this->showForm = false;
    }

    public function addRole()
    {
        $this->validate();

        // التحقق من وجود صلاحيات مختارة
        if (empty($this->selectedPermissions)) {
            session()->flash('error', 'يجب اختيار صلاحية واحدة على الأقل');
            return;
        }

        $role = Role::create([
            'name' => $this->name,
            'guard_name' => 'web',
            'agency_id' => Auth::user()->agency_id,
        ]);

        $role->syncPermissions($this->selectedPermissions);

        $this->closeForm();
        session()->flash('message', 'تم إضافة الدور بنجاح مع ' . count($this->selectedPermissions) . ' صلاحية');
    }

    public function editRole($roleId)
    {
        $role = Role::where('agency_id', Auth::user()->agency_id)->findOrFail($roleId);
        $this->editingRole = $role;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showForm = true;
    }

    public function updateRole()
    {
        $this->validate();
        if (!$this->editingRole) return;
        
        // التحقق من وجود صلاحيات مختارة
        if (empty($this->selectedPermissions)) {
            session()->flash('error', 'يجب اختيار صلاحية واحدة على الأقل');
            return;
        }
        
        $this->editingRole->update(['name' => $this->name]);
        $this->editingRole->syncPermissions($this->selectedPermissions);
        $this->closeForm();
        session()->flash('message', 'تم تحديث الدور بنجاح مع ' . count($this->selectedPermissions) . ' صلاحية');
    }

    public function deleteRole($roleId)
    {
        $role = Role::where('agency_id', Auth::user()->agency_id)->findOrFail($roleId);
        if (in_array($role->name, ['super-admin', 'agency-admin'])) {
            session()->flash('error', 'لا يمكن حذف الأدوار الأساسية');
            return;
        }
        
        // التحقق من وجود مستخدمين مرتبطين بالدور
        if ($role->users()->count() > 0) {
            session()->flash('error', 'لا يمكن حذف الدور لوجود مستخدمين مرتبطين به');
            return;
        }
        
        $role->delete();
        session()->flash('message', 'تم حذف الدور بنجاح');
    }

    public function toggleModule($module)
    {
        $this->openModules[$module] = !($this->openModules[$module] ?? false);
    }

    public function render()
    {
        $roles = Role::where('agency_id', Auth::user()->agency_id)
            ->with('permissions')
            ->withCount('users')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.agency.roles', [
            'roles' => $roles,
            'permissions' => $this->permissions,
        ])
        ->layout('layouts.agency')
        ->title('إدارة الأدوار - ' . Auth::user()->agency->name);
    }
}
