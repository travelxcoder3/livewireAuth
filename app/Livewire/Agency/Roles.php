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

    protected $rules = [
        'name' => 'required|string|max:255',
        'selectedPermissions' => 'required|array|min:1',
        'selectedPermissions.*' => 'exists:permissions,name',
    ];

    public function mount()
    {
        $this->loadPermissions();
    }

    public function loadPermissions()
    {
        $this->permissions = Permission::where('agency_id', Auth::user()->agency_id)->get();
    }

    public function addRole()
    {
        $this->validate();

        $role = Role::create([
            'name' => $this->name,
            'guard_name' => 'web',
            'agency_id' => Auth::user()->agency_id,
        ]);

        $role->syncPermissions($this->selectedPermissions);

        $this->reset(['name', 'selectedPermissions', 'showForm', 'editingRole']);
        session()->flash('message', 'تم إضافة الدور بنجاح');
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
        $this->editingRole->update(['name' => $this->name]);
        $this->editingRole->syncPermissions($this->selectedPermissions);
        $this->reset(['name', 'selectedPermissions', 'showForm', 'editingRole']);
        session()->flash('message', 'تم تحديث الدور بنجاح');
    }

    public function deleteRole($roleId)
    {
        $role = Role::where('agency_id', Auth::user()->agency_id)->findOrFail($roleId);
        if (in_array($role->name, ['super-admin', 'agency-admin'])) {
            session()->flash('message', 'لا يمكن حذف الأدوار الأساسية');
            return;
        }
        $role->delete();
        session()->flash('message', 'تم حذف الدور بنجاح');
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
