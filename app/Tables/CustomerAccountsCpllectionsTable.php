<?php
namespace App\Tables;

class CustomerAccountsCpllectionsTable
{
    public static function columns()
    {
        return [
            ['key' => 'index', 'label' => '#', 'format' => 'serial'],
            ['key' => 'name', 'label' => 'اسم العميل'],

            [
                'key' => 'type',
                'label' => 'النوع',
                'format' => fn($value) => $value > 0 ? 'على العميل' : ($value < 0 ? 'للعميل' : 'متوازن'),
            ],
[
    'key' => 'actions',
    'label' => 'تفاصيل',
    'actions' => [
        [
            'type' => 'link',
            'label' => 'تفاصيل',
            'icon' => 'fas fa-eye',
'url' => '/agency/customer-accounts/:id/history',
            'class' => 'text-blue-600 hover:text-blue-800',
        ],
    ],
],





        ];
    }
}
