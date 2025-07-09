<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Intermediary;

class IntermediarySeeder extends Seeder
{
    public function run(): void
    {
        $intermediaries = ['أحمد الوسيط', 'مؤسسة الوسيط الدولي', 'مكتب الوسيط السياحي'];

        foreach ($intermediaries as $name) {
            Intermediary::create([
                'agency_id' => 1, // تأكد أن agency_id = 1 موجود فعلاً
                'name'      => $name,
            ]);
        }
    }
}
