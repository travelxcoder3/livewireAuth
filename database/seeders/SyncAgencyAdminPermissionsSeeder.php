<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agency;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SyncAgencyAdminPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Agency::all() as $agency) {
            $role = Role::where('name', 'agency-admin')->where('agency_id', $agency->id)->first();
            if ($role) {
                $permissions = Permission::where('agency_id', $agency->id)->pluck('name')->toArray();
                $role->syncPermissions($permissions);
            }
        }
    }
} 