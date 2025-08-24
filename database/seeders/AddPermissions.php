<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AddPermissions extends Seeder
{
    public function run(): void
    {
        $perms = [
            'collection.emp.details.view',
            'collection.employee.view',
            'reportProvider.view',
            'commission-policies.view',
            'reportQuotation.view',
            'backup.view',
        ];

        foreach ($perms as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
