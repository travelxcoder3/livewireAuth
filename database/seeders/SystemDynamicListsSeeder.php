<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DynamicList;

class SystemDynamicListsSeeder extends Seeder
{
    /**
     * أسماء القوائم النظامية فقط (بدون بنود أو بنود فرعية)
     */
    private array $systemLists = [
        'قائمة الخدمات',
        'قائمة الاقسام',
        'قائمة المسمى الوظيفي',
    ];

    /**
     * تنفيذ التهيئة
     */
    public function run(): void
    {
        foreach ($this->systemLists as $name) {
            DynamicList::create([
                'name' => $name,
                'is_system' => true,
                'agency_id' => null,
            ]);
        }
    }
}
