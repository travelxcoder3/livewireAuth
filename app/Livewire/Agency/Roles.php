<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class Roles extends Component
{
    public $roles;
    public $permissions;
    public $showAddModal = false;
    public $showEditModal = false;
    public $editingRole = null;
    
    // حقول إضافة دور جديد
    public $name = '';
    public $selectedPermissions = [];
    
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
        $this->loadRoles();
        $this->loadPermissions();
    }

    public function loadRoles()
    {
        $this->roles = Role::where('agency_id', Auth::user()->agency_id)
            ->with('permissions')
            ->get();
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
        
        $this->reset(['name', 'selectedPermissions']);
        $this->showAddModal = false;
        $this->loadRoles();
        
        session()->flash('success', 'تم إضافة الدور بنجاح');
    }

    public function editRole($roleId)
    {
        $this->editingRole = Role::where('agency_id', Auth::user()->agency_id)->findOrFail($roleId);
        $this->edit_name = $this->editingRole->name;
        $this->edit_selectedPermissions = $this->editingRole->permissions->pluck('name')->toArray();
        $this->showEditModal = true;
    }

    public function updateRole()
    {
        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_selectedPermissions' => 'required|array|min:1',
            'edit_selectedPermissions.*' => 'exists:permissions,name',
        ]);
        
        $this->editingRole->update(['name' => $this->edit_name]);
        $this->editingRole->syncPermissions($this->edit_selectedPermissions);
        
        $this->showEditModal = false;
        $this->editingRole = null;
        $this->loadRoles();
        
        session()->flash('success', 'تم تحديث الدور بنجاح');
    }

    public function deleteRole($roleId)
    {
        $role = Role::where('agency_id', Auth::user()->agency_id)->findOrFail($roleId);
        
        // لا يمكن حذف الأدوار الأساسية
        if (in_array($role->name, ['super-admin', 'agency-admin'])) {
            session()->flash('error', 'لا يمكن حذف الأدوار الأساسية');
            return;
        }
        
        $role->delete();
        
        $this->loadRoles();
        session()->flash('success', 'تم حذف الدور بنجاح');
    }

    public function render()
    {
        return view('livewire.agency.roles')
            ->layout('layouts.agency')
            ->title('إدارة الأدوار - ' . Auth::user()->agency->name);
    }
} 