<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    // الصلاحيات الأساسية التي سيتم إنشاؤها لكل وكالة
    protected $basicPermissions = [
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',
        'roles.view',
        'roles.create',
        'roles.edit',
        'roles.delete',
        'permissions.view',
        'permissions.create',
        'permissions.edit',
        'permissions.delete',
        'reports.view',
        'reports.export',
        'settings.view',
        'settings.edit',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // يمكن تركها فارغة أو استخدامها إذا أردت seeding عام
    }

    /**
     * إنشاء الصلاحيات الأساسية لوكالة معينة
     */
    public function createPermissionsForAgency($agencyId)
    {
        foreach ($this->basicPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
                'agency_id' => $agencyId,
            ]);
        }
    }
} 