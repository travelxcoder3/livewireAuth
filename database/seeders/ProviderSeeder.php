<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Provider;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['name' => 'الخطوط السعودية', 'type' => 'airline'],
            ['name' => 'فندق ماريوت', 'type' => 'hotel'],
            ['name' => 'شركة يورب كار', 'type' => 'car_rental'],
        ];

        foreach ($providers as $provider) {
            Provider::create([
                'agency_id' => 1, // تأكد أن agency_id = 1 موجود فعلاً
                'name'       => $provider['name'],
                'type'       => $provider['type'],
                'contact_info' => json_encode([]),
            ]);
        }
    }
}
