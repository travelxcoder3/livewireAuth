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
                'sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.report',
                'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
                'providers.view', 'providers.create', 'providers.edit', 'providers.delete',
                'service_types.view', 'service_types.create', 'service_types.edit', 'service_types.delete',
                'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
                'lists.view', 'lists.create', 'lists.edit', 'lists.delete',
                'sequences.view', 'sequences.create', 'sequences.edit', 'sequences.delete',
                'agency.profile.view', 'agency.profile.edit',
                'currency.view', 'currency.edit',
                'system.settings.view', 'system.settings.edit',
                'theme.view', 'theme.edit',
                'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
                'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
                'positions.view', 'positions.create', 'positions.edit', 'positions.delete',
                'intermediaries.view', 'intermediaries.create', 'intermediaries.edit', 'intermediaries.delete',
                'accounts.view', 'accounts.create', 'accounts.edit', 'accounts.delete',
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