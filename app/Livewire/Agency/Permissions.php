<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class Permissions extends Component
{
    public $permissions;
    public $showAddButton = false;

    public function mount()
    {
        $this->loadPermissions();
        
        // تحديد ما إذا كان يجب إظهار زر الإضافة
        $this->showAddButton = $this->shouldShowAddButton();
    }

    public function loadPermissions()
    {
        // جلب الصلاحيات العامة لجميع الوكالات
        $this->permissions = Permission::whereNull('agency_id')->get();
    }







    public function shouldShowAddButton()
    {
        // لا نريد إظهار أزرار الإضافة - الصلاحيات ثابتة من قاعدة البيانات
        return false;
    }

    public function render()
    {
        // التحقق من وجود الصلاحيات الأساسية
        $basicPermissionsCount = Permission::whereNull('agency_id')
            ->whereIn('name', [
                'sales.view', 'sales.create', 'sales.edit', 'sales.report',
                'customers.view', 'customers.create', 'customers.edit', 
                'providers.view', 'providers.create', 'providers.edit', 
                
                'employees.view', 'employees.create', 'employees.edit',
                'users.view', 'users.create', 'users.edit',
                'roles.view', 'roles.create', 'roles.edit','roles.delete',
                'permissions.view',
                'lists.view', 'lists.create', 'lists.edit', 'lists.delete',
                'sequences.view', 'sequences.create', 'sequences.edit', 
                'accounts.view', 'accounts.create', 'accounts.edit',
                'sales.reports.view',
            ])
            ->count();

        return view('livewire.agency.permissions', [
            'basicPermissionsCount' => $basicPermissionsCount,
        ])
        ->layout('layouts.agency')
        ->title('عرض الصلاحيات - ' . Auth::user()->agency->name);
    }
} 