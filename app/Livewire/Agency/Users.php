<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Users extends Component
{
    public $users;
    public $roles;
    public $showAddModal = false;
    public $showEditModal = false;
    public $editingUser = null;
    
    // حقول إضافة مستخدم جديد
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = '';
    public $is_active = true;
    
    // حقول تعديل المستخدم
    public $edit_name = '';
    public $edit_email = '';
    public $edit_password = '';
    public $edit_role = '';
    public $edit_is_active = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
        'role' => 'required|exists:roles,name',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->loadUsers();
        $this->loadRoles();
    }

    public function loadUsers()
    {
        $this->users = User::where('agency_id', Auth::user()->agency_id)
            ->where('id', '!=', Auth::user()->id)
            ->with('roles')
            ->get();
    }

    public function loadRoles()
    {
        $this->roles = Role::where('agency_id', Auth::user()->agency_id)->get();
    }

    public function addUser()
    {
        $agency = Auth::user()->agency;
        if ($agency->users()->count() >= $agency->max_users) {
            session()->flash('error', 'لا يمكنك إضافة مستخدمين جدد. لقد وصلت إلى الحد الأقصى المسموح به لهذه الوكالة.');
            return;
        }
        $this->validate();
        
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'agency_id' => Auth::user()->agency_id,
            'is_active' => $this->is_active,
        ]);
        
        $user->assignRole($this->role);
        // تحديث صلاحيات دور أدمن الوكالة إذا كان الدور هو agency-admin
        if ($this->role === 'agency-admin') {
            $agencyAdminRole = Role::where('name', 'agency-admin')
                ->where('agency_id', Auth::user()->agency_id)
                ->first();
            if ($agencyAdminRole) {
                $allPermissions = \Spatie\Permission\Models\Permission::where('agency_id', Auth::user()->agency_id)->pluck('name')->toArray();
                $agencyAdminRole->syncPermissions($allPermissions);
            }
        }
        
        $this->reset(['name', 'email', 'password', 'role', 'is_active']);
        $this->showAddModal = false;
        $this->loadUsers();
        
        session()->flash('success', 'تم إضافة المستخدم بنجاح');
    }

    public function editUser($userId)
    {
        $this->editingUser = User::findOrFail($userId);
        $this->edit_name = $this->editingUser->name;
        $this->edit_email = $this->editingUser->email;
        $this->edit_role = $this->editingUser->roles->first()->name ?? '';
        $this->edit_is_active = $this->editingUser->is_active;
        $this->showEditModal = true;
    }

    public function updateUser()
    {
        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_email' => 'required|email|unique:users,email,' . $this->editingUser->id,
            'edit_password' => 'nullable|string|min:6',
            'edit_role' => 'required|exists:roles,name',
            'edit_is_active' => 'boolean',
        ]);
        
        $this->editingUser->update([
            'name' => $this->edit_name,
            'email' => $this->edit_email,
            'is_active' => $this->edit_is_active,
        ]);
        
        if ($this->edit_password) {
            $this->editingUser->update(['password' => Hash::make($this->edit_password)]);
        }
        
        $this->editingUser->syncRoles([$this->edit_role]);
        // تحديث صلاحيات دور أدمن الوكالة إذا كان الدور هو agency-admin
        if ($this->edit_role === 'agency-admin') {
            $agencyAdminRole = Role::where('name', 'agency-admin')
                ->where('agency_id', Auth::user()->agency_id)
                ->first();
            if ($agencyAdminRole) {
                $allPermissions = \Spatie\Permission\Models\Permission::where('agency_id', Auth::user()->agency_id)->pluck('name')->toArray();
                $agencyAdminRole->syncPermissions($allPermissions);
            }
        }
        
        $this->showEditModal = false;
        $this->editingUser = null;
        $this->loadUsers();
        
        session()->flash('success', 'تم تحديث المستخدم بنجاح');
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();
        
        $this->loadUsers();
        session()->flash('success', 'تم حذف المستخدم بنجاح');
    }

    public function toggleUserStatus($userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['is_active' => !$user->is_active]);
        
        $this->loadUsers();
        session()->flash('success', 'تم تغيير حالة المستخدم بنجاح');
    }

    public function closeModal()
    {
        $this->reset(['name', 'email', 'password', 'role', 'is_active', 'edit_name', 'edit_email', 'edit_password', 'edit_role', 'edit_is_active', 'showAddModal', 'showEditModal', 'editingUser']);
    }

    public function render()
    {
        return view('livewire.agency.users')
            ->layout('layouts.agency')
            ->title('إدارة المستخدمين - ' . Auth::user()->agency->name);
    }
} 