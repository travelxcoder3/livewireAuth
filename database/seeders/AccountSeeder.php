<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Agency;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // جلب جميع الوكالات الموجودة
        $agencies = Agency::all();

        foreach ($agencies as $agency) {
            Account::create([
                'agency_id' => $agency->id,
                'name' => 'الخزينة الرئيسية',
                'type' => 'cash',
                'is_active' => true,
            ]);

            Account::create([
                'agency_id' => $agency->id,
                'name' => 'حساب بنك الكريمي',
                'type' => 'bank',
                'is_active' => true,
            ]);

            Account::create([
                'agency_id' => $agency->id,
                'name' => 'بطاقة فيزا السفر',
                'type' => 'visa',
                'is_active' => true,
            ]);
        }
    }
}
