<?php

namespace App\Tables;

class CustomerCollectionDetailsTable
{
    public static function columns()
    {
        return [
            ['key' => 'beneficiary_name', 'label' => 'اسم المستفيد'],
            ['key' => 'sale_date', 'label' => 'تاريخ البيع'],
            [
                'key' => 'usd_sell',
                'label' => 'قيمة البيع',
                'format' => 'money',
                'color' => 'blue-700',
            ],
            [
                'key' => 'collected',
                'label' => 'المحصل',
                'format' => 'money',
                'color' => 'green-700',
            ],
            [
                'key' => 'remaining',
                'label' => 'المتبقي',
                'format' => 'money',
                'color' => 'red-600',
            ],
            ['key' => 'note', 'label' => 'ملاحظات'],
        ];
    }
}
