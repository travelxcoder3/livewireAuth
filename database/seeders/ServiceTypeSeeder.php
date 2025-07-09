<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceType;
use App\Models\Agency;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $agency = Agency::first() ?? Agency::factory()->create();

        $types = ['تذاكر', 'فنادق', 'سيارات', 'تأشيرات', 'باقات سياحية'];

        foreach ($types as $type) {
            ServiceType::create([
                'name' => $type,
                'agency_id' => $agency->id,
            ]);
        }
    }
}
