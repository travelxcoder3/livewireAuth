<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DynamicList;
use App\Models\DynamicListItem;
use App\Models\Provider;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $list = DynamicList::firstOrCreate([
            'name' => 'قائمة الخدمات',
            'is_system' => true,
            'agency_id' => null,
        ]);

        $services = [
            ['label' => 'طيران'],
            ['label' => 'فنادق'],
            ['label' => 'تأجير سيارات'],
        ];

        foreach ($services as $index => $service) {
            DynamicListItem::firstOrCreate([
                'dynamic_list_id' => $list->id,
                'label' => $service['label'],
            ], [
                'order' => $index + 1,
                'is_system' => true,
            ]);
        }

        $firstService = DynamicListItem::where('dynamic_list_id', $list->id)
            ->where('label', 'طيران')
            ->first();

        if ($firstService) {
            Provider::first()?->update([
                'service_item_id' => $firstService->id,
            ]);
        }
    }
}
