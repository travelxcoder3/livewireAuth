<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agency;
use Database\Seeders\PermissionSeeder;

class CreatePermissionsForExistingAgenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionSeeder = new PermissionSeeder();
        
        // الحصول على جميع الوكالات
        $agencies = Agency::all();
        
        foreach ($agencies as $agency) {
            // إنشاء الصلاحيات الأساسية لكل وكالة
            $permissionSeeder->createPermissionsForAgency($agency->id);
            
            // إنشاء دور agency-admin لكل وكالة
            $agency->createAgencyAdminRole();
            
            echo "تم إنشاء الصلاحيات والأدوار لوكالة: {$agency->name}\n";
        }
        
        echo "تم إنشاء الصلاحيات والأدوار لجميع الوكالات بنجاح!\n";
    }
} 