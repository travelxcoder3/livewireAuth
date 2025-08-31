<?php

namespace App\Tables;

class CustomerAccountsCollectionsdetailsTable
{
    public static function columns(): array
    {
        return [
            ['key' => 'index',              'label' => '#',                      'format' => 'serial'],
            ['key' => 'name',               'label' => 'العميل'],
            ['key' => 'invoice_total_true', 'label' => 'سعر الخدمة (أصل)',      'format' => 'money', 'color' => 'blue-700'],
            ['key' => 'refund_total',       'label' => 'الاستردادات',            'format' => 'money', 'color' => 'rose-700'],
            ['key' => 'total_collected',    'label' => 'المحصل (إجمالي)',        'format' => 'money', 'color' => 'green-700'],

            // المتبقي/رصيد للعميل كنص جاهز من المنطق لديك (مثال: "مدين: 120" أو "للعميل: 50" أو "تم السداد بالكامل")
            ['key' => 'balance_text',       'label' => 'المتبقي / رصيد للعميل'],

            [
                'key'     => 'actions',
                'label'   => 'الإجراءات',
                'format'  => 'custom',
                'actions' => [
                    [
                        'label' => 'تفاصيل',
                        'icon'  => 'fas fa-eye',
                        // يدعم استبدال :id تلقائياً في الـ component
                        'url'   => '/agency/customer-accounts/:id/history',
                        'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                    ],
                ],
            ],
        ];
    }
}
