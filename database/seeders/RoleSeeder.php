<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'agency-admin']);
        Role::firstOrCreate(['name' => 'user']);

        // إنشاء مستخدم super-admin افتراضي إذا لم يكن موجوداً
        $user = \App\Models\User::firstOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
            'agency_id' => null,
        ]);
        $user->assignRole('super-admin');

        // إنشاء صلاحيات الموظفين
        $permissions = [
            'employees.create',
            'employees.view',
            'employees.update',
            'employees.delete',
        ];
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // إنشاء دور hr وإعطاؤه صلاحيات الموظفين
        $hrRole = Role::firstOrCreate([
            'name' => 'hr',
            'guard_name' => 'web',
        ]);
        $hrRole->givePermissionTo($permissions);

        // صلاحيات أدمن الوكالة
        $agencyAdminPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.manage',
            // صلاحيات المبيعات
            'sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.report',
        ];
        foreach ($agencyAdminPermissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
        $agencyAdminRole = Role::firstOrCreate([
            'name' => 'agency-admin',
            'guard_name' => 'web',
        ]);
        $agencyAdminRole->givePermissionTo($agencyAdminPermissions);
    }
} 