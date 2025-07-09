<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DynamicListItemSub;

class CollectionsTableSeeder extends Seeder
{
    public function run(): void
    {
        // نوع العميل
        $customerTypes = DynamicListItemSub::whereHas('parentItem', function ($q) {
            $q->where('label', 'نوع العميل');
        })->get();

        // نوع المديونية
        $debtTypes = DynamicListItemSub::whereHas('parentItem', function ($q) {
            $q->where('label', 'نوع المديونية');
        })->get();

        // تجاوب العميل
        $customerResponses = DynamicListItemSub::whereHas('parentItem', function ($q) {
            $q->where('label', 'تجاوب العميل');
        })->get();

        // نوع ارتباطه بالشركة
        $customerRelations = DynamicListItemSub::whereHas('parentItem', function ($q) {
            $q->where('label', 'نوع ارتباطه بالشركة');
        })->get();

        // سبب التأخر
        $delayReasons = DynamicListItemSub::whereHas('parentItem', function ($q) {
            $q->where('label', 'سبب التأخر');
        })->get();

        // فقط لأغراض التحقق / العرض
        $this->command->info("تم جلب البنود بنجاح:");
        $this->command->info("نوع العميل: " . $customerTypes->count());
        $this->command->info("نوع المديونية: " . $debtTypes->count());
        $this->command->info("تجاوب العميل: " . $customerResponses->count());
        $this->command->info("نوع الارتباط: " . $customerRelations->count());
        $this->command->info("سبب التأخر: " . $delayReasons->count());
    }
}
