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
    public $is_active = false;
    
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
        $agency = Auth::user()->agency;
        if ($agency && $agency->parent_id === null) {
            // وكالة رئيسية: اعرض جميع المستخدمين في الوكالة وفروعها
            $this->users = $agency->allUsersWithBranches();
        } else {
            // فرع: اعرض فقط مستخدمي الفرع
            $this->users = $agency->users;
        }
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
        
       $role = Role::where('name', $this->role)
    ->where('guard_name', 'web')
    ->where('agency_id', Auth::user()->agency_id)
    ->firstOrFail();
$user->assignRole($role);

        // تحديث صلاحيات دور أدمن الوكالة إذا كان الدور هو agency-admin
        if ($this->role === 'agency-admin') {
            $agencyAdminRole = Role::where('name', 'agency-admin')
                ->where('agency_id', Auth::user()->agency_id)
                ->first();
            if ($agencyAdminRole) {
                $allPermissions = \Spatie\Permission\Models\Permission::where(function($q) {
                    $q->where('agency_id', Auth::user()->agency_id)
                      ->orWhereNull('agency_id');
                })->pluck('name')->toArray();
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
        $user = User::findOrFail($userId);
        if ($user->agency_id != Auth::user()->agency_id) {
            abort(403, 'غير مصرح لك بتعديل مستخدمي الفروع');
        }
        $this->editingUser = $user;
        $this->edit_name = $user->name;
        $this->edit_email = $user->email;
        $this->edit_role = $user->roles->first()->name ?? '';
        $this->edit_is_active = $user->is_active;
        $this->showEditModal = true;
    }

    public function updateUser()
    {
        $user = $this->editingUser;
        if ($user->agency_id != Auth::user()->agency_id) {
            abort(403, 'غير مصرح لك بتحديث مستخدمي الفروع');
        }

    
        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_email' => 'required|email|unique:users,email,' . $user->id,
            'edit_password' => 'nullable|string|min:6',
            'edit_role' => 'required|exists:roles,name',
            'edit_is_active' => 'boolean',
        ]);
       $user->update([
            'name' => $this->edit_name,
            'email' => $this->edit_email,
            'is_active' => $this->edit_is_active,
        ]);

        if ($this->edit_password) {
            $user->update(['password' => Hash::make($this->edit_password)]);
        }
        $role = Role::where('name', $this->edit_role)
    ->where('guard_name', 'web')
    ->where('agency_id', Auth::user()->agency_id)
    ->firstOrFail();
$user->syncRoles([$role]);

        if ($this->edit_role === 'agency-admin') {
            $agencyAdminRole = Role::where('name', 'agency-admin')
                ->where('agency_id', Auth::user()->agency_id)
                ->first();
            if ($agencyAdminRole) {
                $allPermissions = \Spatie\Permission\Models\Permission::where(function($q) {
                    $q->where('agency_id', Auth::user()->agency_id)
                      ->orWhereNull('agency_id');
                })->pluck('name')->toArray();
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
        if ($user->agency_id != Auth::user()->agency_id) {
            abort(403, 'غير مصرح لك بحذف مستخدمي الفروع');
        }
        $user->delete();
        $this->loadUsers();
        session()->flash('success', 'تم حذف المستخدم بنجاح');
    }

    public function toggleUserStatus($userId)
    {
        $user = User::findOrFail($userId);
        if ($user->agency_id != Auth::user()->agency_id) {
            abort(403, 'غير مصرح لك بتغيير حالة مستخدمي الفروع');
        }
        $user->update(['is_active' => !$user->is_active]);
        $this->loadUsers();
        session()->flash('success', 'تم تغيير حالة المستخدم بنجاح');
    }

    public function closeModal()
{
    $this->reset([
        'name','email','password','role','is_active',
        'edit_name','edit_email','edit_password','edit_role','edit_is_active',
        'showAddModal','showEditModal','editingUser',
    ]);

    $this->resetValidation();
}


 


    // أضف هذه الخصائص في بداية الكلاس
public $search = '';
public $role_filter = '';
public $status_filter = '';

// عدل دالة render لتشمل الفلاتر
public function render()
{
    $agency = Auth::user()->agency;
    
    $query = User::query()
        ->with(['roles', 'agency'])
        ->when($agency->parent_id === null, function ($query) use ($agency) {
            $query->whereIn('agency_id', array_merge([$agency->id], $agency->branches()->pluck('id')->toArray()));
        }, function ($query) use ($agency) {
            $query->where('agency_id', $agency->id);
        })
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        })
        ->when($this->role_filter, function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->role_filter);
            });
        })
        ->when($this->status_filter !== '', function ($query) {
            $query->where('is_active', $this->status_filter);
        })
        ->latest();

    $this->users = $query->get();

    return view('livewire.agency.users')
        ->layout('layouts.agency')
        ->title('إدارة المستخدمين - ' . $agency->name);
}

// أضف دالة لإعادة تعيين الفلاتر
public function resetFilters()
{
    $this->reset(['search', 'role_filter', 'status_filter']);
}
} 