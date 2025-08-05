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
                'key' => 'account_type',
                'label' => 'نوع الحساب',
                'format' => fn($value) => match ($value) {
                    'individual' => 'فرد',
                    'company' => 'شركة',
                    'organization' => 'منظمة',
                    default => 'غير محدد',
                },
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
