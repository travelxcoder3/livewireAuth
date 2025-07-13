<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SyncAgencyAdminPermissionsSeeder extends Seeder
{
    public function run()
    {
        $allPermissions = Permission::pluck('name')->toArray();
        $roles = Role::where('name', 'agency-admin')->get();
        foreach ($roles as $role) {
            $role->syncPermissions($allPermissions);
        }
        $this->command->info('تم تحديث جميع أدوار agency-admin بكل الصلاحيات بنجاح.');
    }
} 