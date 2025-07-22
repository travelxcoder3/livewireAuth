<?php

namespace App\Tables;

class CollectionTable
{
   public static function columns()
{
    return [
        ['key' => 'index', 'label' => '#'],
        ['key' => 'beneficiary_name', 'label' => 'اسم العميل'],
        ['key' => 'remaining', 'label' => 'الرصيد', 'format' => 'money', 'color' => 'red-600'],
        ['key' => 'last_payment', 'label' => 'آخر سداد', 'format' => 'date'], 
        ['key' => 'debt_age', 'label' => 'عمر الدين'],
        ['key' => 'expected_payment_date', 'label' => 'تاريخ السداد المتوقع', 'format' => 'date'],
        ['key' => 'customer_type', 'label' => 'نوع العميل'],
        ['key' => 'debt_type', 'label' => 'نوع المديونية'],
        ['key' => 'customer_response', 'label' => 'تجاوب العميل'],
        ['key' => 'customer_relation', 'label' => 'نوع الارتباط'],
        [
            'key' => 'actions',
            'label' => 'الإجراء',
            'actions' => [
                [
                    'type' => 'details',
                    'label' => 'تفاصيل المديونية',
                    'url' => function($row) { return route('agency.collection.details', $row->id); },
                    'class' => 'text-white text-xs px-3 py-1 bg-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-600))] rounded-lg shadow',
                ]
            ]
        ]
    ];
}
} 