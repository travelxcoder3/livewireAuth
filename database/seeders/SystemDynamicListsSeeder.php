<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DynamicList;
use App\Models\DynamicListItem;
use App\Models\DynamicListItemSub;

class SystemDynamicListsSeeder extends Seeder
{
    /**
     * قوائم النظام الأساسية
     */
    private array $systemLists = [
        [
            'name' => 'قائمة الخدمات',
            'original_id' => 1,
            'items' => []
        ],
        [
            'name' => 'قائمة الحالات',
            'original_id' => 2,
            'items' => []
        ],
        [
            'name' => 'قائمة التحصيل',
            'original_id' => 3,
            'items' => []
        ],
        [
            'name' => 'طرق التسديد',
            'original_id' => 4,
            'items' => []
        ],
        [
            'name' => 'الصناديق والبنوك',
            'original_id' => 5,
            'items' => []
        ],
        [
            'name' => 'قائمة الأقسام',
            'original_id' => 6,
            'items' => []
        ],
        [
            'name' => 'قائمة المسمى الوظيفي',
            'original_id' => 7,
            'items' => []
        ],
    ];

    /**
     * تنفيذ عملية التهيئة
     */
    public function run(): void
    {
        foreach ($this->systemLists as $listData) {
            $this->createListWithItems($listData);
        }
    }

    /**
     * إنشاء قائمة مع بنودها وبنودها الفرعية
     */
    protected function createListWithItems(array $listData): void
    {
        $list = DynamicList::create([
            'name' => $listData['name'],
            'is_system' => true,
            'agency_id' => null,
            'original_id' => $listData['original_id'],
        ]);

        foreach ($listData['items'] as $itemData) {
            $this->createListItem($list, $itemData);
        }
    }

    /**
     * إنشاء بند قائمة مع بنوده الفرعية
     */
    protected function createListItem(DynamicList $list, array $itemData): void
    {
        $item = DynamicListItem::create([
            'dynamic_list_id' => $list->id,
            'label' => $itemData['label'],
        ]);

        foreach ($itemData['sub_items'] ?? [] as $subItemLabel) {
            DynamicListItemSub::create([
                'dynamic_list_item_id' => $item->id,
                'label' => $subItemLabel,
            ]);
        }
    }
}
