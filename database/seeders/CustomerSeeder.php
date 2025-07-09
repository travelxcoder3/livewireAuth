<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $agency = DB::table('agencies')->where('email', 'future@agency.com')->first();

        if (!$agency) {
            return; // لا توجد وكالة
        }

        $customers = [
            [
                'name' => 'أحمد علي',
                'email' => 'ahmed@example.com',
                'phone' => '777123456',
                'address' => 'صنعاء',
            ],
            [
                'name' => 'منى سعيد',
                'email' => 'mona@example.com',
                'phone' => '777987654',
                'address' => 'عدن',
            ],
            [
                'name' => 'فهد الحبيشي',
                'email' => 'fahd@example.com',
                'phone' => '773345678',
                'address' => 'الحديدة',
            ],
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->insert([
                'agency_id' => $agency->id,
                'name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
                'address' => $customer['address'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
