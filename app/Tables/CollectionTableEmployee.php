<?php

namespace App\Tables;

class CollectionTableEmployee
{
 public static function columns()
{
    return [
        ['key' => 'index', 'label' => '#'],
        ['key' => 'name', 'label' => 'اسم العميل'],
        [
            'key' => 'remaining_for_customer',
            'label' => 'المديوينية',
            'format' => 'money',
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
                'icon'  => 'fas fa-eye',
                'can' => auth()->user()->can('collection.details.view'),
                'url'   => fn($row) => route('agency.collection.details.employee', $row->first_sale_id),
                'class' => 'text-[rgb(var(--primary-600))] hover:text-black font-medium text-xs transition',
            ],


            ]
        ]
    ];
}

} 