<?php

namespace App\Tables;

class CollectionTable
{
 public static function columns()
{
    return [
        ['key' => 'index', 'label' => '#'],
        ['key' => 'name', 'label' => 'اسم العميل'],
[
    'key' => 'remaining_for_customer',
    'label' => 'متبقي على العميل',
    'format' => 'money',
],
[
    'key' => 'remaining_for_company',
    'label' => 'متبقي للعميل',
    'format' => 'money',
],
[
    'key' => 'net_due',
    'label' => 'الفارق ',
    'format' => 'money',
    'color' => fn($value) => $value > 0 ? 'red-700' : ($value < 0 ? 'green-700' : 'gray-400'),
],

        ['key' => 'last_payment', 'label' => 'آخر سداد', 'format' => 'date'],
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
                    'label' => 'تفاصيل',
                    'url' => fn($row) => route('agency.collection.details', $row->first_sale_id),


                    'class' => 'text-white text-xs px-3 py-1 bg-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-600))] rounded-lg shadow',
                ]
            ]
        ]
    ];
}

} 