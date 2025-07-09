<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run()
    {
        SystemSetting::firstOrCreate([], [
            'theme_color' => 'emerald'
        ]);
    }
}