<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class Permissions extends Component
{
    public $permissions;
    public $showAddModal = false;
    public $showEditModal = false;
    public $editingPermission = null;
    
    // حقول إضافة صلاحية جديدة
    public $name = '';
    
    // حقول تعديل الصلاحية
    public $edit_name = '';

    protected $rules = [
        'name' => 'required|string|max:255',
    ];

    public function mount()
    {
        $this->loadPermissions();
        
        // إنشاء الصلاحيات الأساسية إذا لم تكن موجودة
        $this->createBasicPermissionsIfNeeded();
    }

    public function createBasicPermissionsIfNeeded()
    {
        $basicPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
            'reports.view', 'reports.export',
            'settings.view', 'settings.edit',
        ];
        
        $existingPermissions = Permission::where('agency_id', Auth::user()->agency_id)
            ->whereIn('name', $basicPermissions)
            ->pluck('name')
            ->toArray();
            
        $missingPermissions = array_diff($basicPermissions, $existingPermissions);
        
        if (!empty($missingPermissions)) {
            foreach ($missingPermissions as $permissionName) {
                Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'agency_id' => Auth::user()->agency_id,
                ]);
            }
            
            // إنشاء دور agency-admin إذا لم يكن موجودًا
            $this->createAgencyAdminRoleIfNeeded();
            
            // إعادة تحميل الصلاحيات
            $this->loadPermissions();
            
            session()->flash('success', 'تم إنشاء الصلاحيات الأساسية تلقائيًا');
        }
    }

    public function createAgencyAdminRoleIfNeeded()
    {
        $agencyAdminRole = Role::where('name', 'agency-admin')
            ->where('agency_id', Auth::user()->agency_id)
            ->first();
            
        if (!$agencyAdminRole) {
            $agencyAdminRole = Role::create([
                'name' => 'agency-admin',
                'guard_name' => 'web',
                'agency_id' => Auth::user()->agency_id,
            ]);
            
            // ربط الدور بجميع الصلاحيات
            $allPermissions = Permission::where('agency_id', Auth::user()->agency_id)->pluck('name')->toArray();
            $agencyAdminRole->givePermissionTo($allPermissions);
        }
    }

    public function loadPermissions()
    {
        $this->permissions = Permission::where('agency_id', Auth::user()->agency_id)
            ->withCount('roles')
            ->get();
    }

    public function addPermission()
    {
        $this->validate();
        
        // تحقق من وجود الصلاحية مسبقاً لنفس الوكالة
        $exists = \Spatie\Permission\Models\Permission::where('name', $this->name)
            ->where('agency_id', Auth::user()->agency_id)
            ->where('guard_name', 'web')
            ->exists();
        if ($exists) {
            session()->flash('error', 'هذه الصلاحية موجودة بالفعل ولا يمكن تكرارها.');
            return;
        }
        
        $permission = \Spatie\Permission\Models\Permission::create([
            'name' => $this->name,
            'guard_name' => 'web',
            'agency_id' => Auth::user()->agency_id,
        ]);
        // ربط الصلاحية الجديدة بدور أدمن الوكالة تلقائيًا
        $agencyAdminRole = \Spatie\Permission\Models\Role::where('name', 'agency-admin')
            ->where('agency_id', Auth::user()->agency_id)
            ->first();
        if ($agencyAdminRole) {
            $agencyAdminRole->givePermissionTo($permission->name);
        }
        
        $this->reset(['name']);
        $this->showAddModal = false;
        $this->loadPermissions();
        
        session()->flash('success', 'تم إضافة الصلاحية بنجاح');
    }

    public function editPermission($permissionId)
    {
        session()->flash('error', 'تعديل الصلاحيات غير مسموح.');
        return;
    }

    public function updatePermission()
    {
        session()->flash('error', 'تعديل الصلاحيات غير مسموح.');
        return;
    }

    public function deletePermission($permissionId)
    {
        session()->flash('error', 'حذف الصلاحيات غير مسموح.');
        return;
    }

    public function createBasicPermissions()
    {
        $permissionSeeder = new \Database\Seeders\PermissionSeeder();
        $permissionSeeder->createPermissionsForAgency(Auth::user()->agency_id);
        
        // إنشاء دور agency-admin
        $agency = Auth::user()->agency;
        $agency->createAgencyAdminRole();
        
        $this->loadPermissions();
        session()->flash('success', 'تم إنشاء الصلاحيات الأساسية بنجاح');
    }

    public function createPermissionsForAllAgencies()
    {
        $agencies = \App\Models\Agency::all();
        $permissionSeeder = new \Database\Seeders\PermissionSeeder();
        
        foreach ($agencies as $agency) {
            $permissionSeeder->createPermissionsForAgency($agency->id);
            $agency->createAgencyAdminRole();
        }
        
        session()->flash('success', "تم إنشاء الصلاحيات لـ {$agencies->count()} وكالة بنجاح");
    }

    public function render()
    {
        // التحقق من وجود الصلاحيات الأساسية
        $basicPermissionsCount = Permission::where('agency_id', Auth::user()->agency_id)
            ->whereIn('name', [
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
                'reports.view', 'reports.export',
                'settings.view', 'settings.edit',
            ])
            ->count();
            
        $showAddButton = $basicPermissionsCount < 16; // عدد الصلاحيات الأساسية
        
        return view('livewire.agency.permissions', compact('showAddButton'))
            ->layout('layouts.agency')
            ->title('إدارة الصلاحيات - ' . Auth::user()->agency->name);
    }
} 