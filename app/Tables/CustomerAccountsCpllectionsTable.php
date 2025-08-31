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
                'key'     => 'actions',
                'label'   => 'الإجراءات',
                'format'  => 'custom',
                'actions' => [
                    [
                        'label' => 'تفاصيل',
                        'icon'  => 'fas fa-eye',
                        'url'   => '/agency/customer-accounts/:id/history',
                        'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))] cursor-default',
                    ],
                ],
            ],




        ];
    }
}
