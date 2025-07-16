<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agency;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء وكالة افتراضية
        $agency = Agency::firstOrCreate([
            'name' => 'AnasWare',
            'email' => 'anasware@example.com',
        ], [
            'phone' => '777777777',
            'address' => 'اليمن - صنعاء',
            'license_number' => 'LIC-0001',
            'commercial_record' => 'CR-0001',
            'tax_number' => 'TAX-0001',
            'logo' => null,
            'description' => 'وكالة تجريبية',
            'status' => 'active',
            'license_expiry_date' => now()->addYear(),
            'max_users' => 10,
            'landline' => null,
            'currency' => 'YER',
            'subscription_start_date' => now(),
            'subscription_end_date' => now()->addYear(),
            'theme_color' => 'emerald',
        ]);

        // إنشاء دور أدمن الوكالة إذا لم يكن موجوداً
        $role = Role::firstOrCreate([
            'name' => 'agency-admin',
            'guard_name' => 'web',
            'agency_id' => $agency->id,
        ]);

        // إنشاء مستخدم أدمن للوكالة
        $admin = User::firstOrCreate([
            'email' => 'anas@gmail.com',
        ], [
            'name' => ' انس حسين',
            'password' => bcrypt('123123'),
            'agency_id' => $agency->id,
        ]);
        $admin->assignRole($role);
    }
} 