<?php

namespace App\Tables;

class CustomerCommissionsTable
{
    public static function columns()
    {
        return [
            ['key' => 'customer', 'label' => 'العميل', 'sortable' => true],
            ['key' => 'amount', 'label' => 'المبلغ', 'format' => 'money', 'sortable' => true],
            ['key' => 'profit', 'label' => 'الربح', 'format' => 'money', 'sortable' => true],
            ['key' => 'commission', 'label' => 'المبلغ المستحقة', 'format' => 'money', 'color' => 'blue-600', 'sortable' => true],
            [
                'key' => 'status', 
                'label' => 'الحالة', 
                'format' => 'status',
               'color' => function($value) {
                    return $value == 'تم التحصيل' ? 'green-600' : 'red-600';
                },

                'sortable' => true
            ],
            ['key' => 'date', 'label' => 'التاريخ', 'sortable' => true],
        ];
    }
}