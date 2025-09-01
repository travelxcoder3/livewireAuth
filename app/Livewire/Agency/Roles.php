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
    public $search = '';


    public $showDeleteModal = false;
public $roleToDelete;

public function confirmDelete($roleId)
{
    $this->roleToDelete = $roleId;
    $this->showDeleteModal = true;
}

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $query = Role::where('name', $value)
                        ->where('guard_name', 'web')
                        ->where('agency_id', auth()->user()->agency_id);
                    
                    if ($this->editingRole) {
                        $query->where('id', '!=', $this->editingRole->id);
                    }
                    
                    if ($query->exists()) {
                        $fail('هذا الاسم مستخدم بالفعل في هذه الوكالة');
                    }
                }
            ],
            'selectedPermissions' => 'required|array|min:1',
            'selectedPermissions.*' => 'exists:permissions,name',
        ];
    }

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

        try {
            // Create the role with the current agency context
            $role = new Role([
                'name' => $this->name,
                'guard_name' => 'web',
                'agency_id' => auth()->user()->agency_id,
            ]);
            
            // Save the role
            $role->save();
            
            // Sync permissions
            $role->syncPermissions($this->selectedPermissions);

            $this->closeForm();
            session()->flash('message', 'تم إضافة الدور بنجاح ');
            
        } catch (\Exception $e) {
            // Log the full error for debugging
            \Log::error('Error creating role: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            
            // Check for duplicate entry error
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                session()->flash('error', 'عذراً، حدث خطأ في إنشاء الدور. يبدو أن هناك تعارض في البيانات.');
            } else {
                session()->flash('error', 'حدث خطأ غير متوقع أثناء إنشاء الدور. يرجى المحاولة مرة أخرى.');
            }
        }
    }

    public function editRole($roleId)
    {
        $role = Role::where('agency_id', Auth::user()->agency_id)->findOrFail($roleId);
        
        // منع تعديل دور agency-admin
        if ($role->name === 'agency-admin') {
            session()->flash('error', 'لا يمكن تعديل الدور الأساسي للوكالة');
            return;
        }
        
        $this->editingRole = $role;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showForm = true;
    }

    public function updateRole()
    {
        $this->validate();
        
        if (!$this->editingRole) {
            session()->flash('error', 'لم يتم العثور على الدور المحدد');
            return;
        }
        
        // منع تحديث دور agency-admin
        if ($this->editingRole->name === 'agency-admin') {
            session()->flash('error', 'لا يمكن تعديل الدور الأساسي للوكالة');
            return;
        }
        
        // التحقق من وجود صلاحيات مختارة
        if (empty($this->selectedPermissions)) {
            session()->flash('error', 'يجب اختيار صلاحية واحدة على الأقل');
            return;
        }
        
        try {
            // Update the role with the current agency context
            $this->editingRole->update([
                'name' => $this->name,
                'agency_id' => auth()->user()->agency_id, // Ensure agency_id is set
            ]);
            
            // Sync permissions
            $this->editingRole->syncPermissions($this->selectedPermissions);
            
            $this->closeForm();
            session()->flash('message', 'تم تحديث الدور بنجاح ');
            
        } catch (\Exception $e) {
            // Log the full error for debugging
            \Log::error('Error updating role: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            
            // Check for duplicate entry error
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                session()->flash('error', 'عذراً، لا يمكن تحديث الدور. يبدو أن الاسم مستخدم بالفعل في هذه الوكالة.');
            } else {
                session()->flash('error', 'حدث خطأ غير متوقع أثناء تحديث الدور. يرجى المحاولة مرة أخرى.');
            }
        }
    }

    public function delete()
{
    $role = Role::where('agency_id', Auth::user()->agency_id)->findOrFail($this->roleToDelete);

    if (in_array($role->name, ['super-admin', 'agency-admin'])) {
        session()->flash('error', 'لا يمكن حذف الأدوار الأساسية');
        return;
    }

    if ($role->users()->count() > 0) {
        session()->flash('error', 'لا يمكن حذف الدور لوجود مستخدمين مرتبطين به');
        return;
    }

    $role->delete();
    session()->flash('message', 'تم حذف الدور بنجاح');

    $this->showDeleteModal = false;
    $this->roleToDelete = null;
}


    public function toggleModule($module)
    {
        $this->openModules[$module] = !($this->openModules[$module] ?? false);
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

$rolesQuery = Role::where('agency_id', $agencyId)
    ->with('permissions')
    ->withCount(['users as users_count' => function ($q) use ($agencyId) {
        $q->where('users.agency_id', $agencyId);
    }]);

        if (!empty($this->search)) {
            $rolesQuery->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }
        $roles = $rolesQuery->orderBy('id', 'desc')->paginate(10);
        return view('livewire.agency.roles', [
            'roles' => $roles,
            'permissions' => $this->permissions,
            'selectedPermissions' => $this->selectedPermissions,
            'showPermissions' => $this->showPermissions,
            'openModules' => $this->openModules,
        ])
        ->layout('layouts.agency')
        ->title('إدارة الأدوار - ' . Auth::user()->agency->name);
    }
}
