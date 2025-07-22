<?php

namespace App\Tables;

class SalesTable
{
    public static function columns()
    {
        return [
            ['key' => 'sale_date', 'label' => 'التاريخ', 'format' => 'date'],
            ['key' => 'beneficiary_name', 'label' => 'المستفيد'],
            ['key' => 'customer.name', 'label' => 'العميل'],
            ['key' => 'service.label', 'label' => 'الخدمة'],
            ['key' => 'provider.name', 'label' => 'المزود'],
            ['key' => 'service_date', 'label' => 'تاريخ الخدمة', 'format' => 'date'],
             ['key' => 'customer_via', 'label' => 'العميل عبر', 'format' => 'customer_via'],
            ['key' => 'usd_buy', 'label' => 'Buy', 'format' => 'money', 'color' => 'primary-500'],
            ['key' => 'usd_sell', 'label' => 'Sell', 'format' => 'money', 'color' => 'primary-600'],
            ['key' => 'sale_profit', 'label' => 'الربح', 'format' => 'money', 'color' => 'primary-700'],
            ['key' => 'amount_paid', 'label' => 'المبلغ المدفوع', 'format' => 'money'],
            ['key' => 'expected_payment_date', 'label' => 'تاريخ السداد المتوقع', 'format' => 'date'],
            ['key' => 'reference', 'label' => 'المرجع'],
            ['key' => 'pnr', 'label' => 'PNR'],
            ['key' => 'route', 'label' => 'Route'],
            ['key' => 'status', 'label' => 'الحالة', 'format' => 'status'],
            ['key' => 'user.name', 'label' => 'الموظف'],
            ['key' => 'agency.name', 'label' => 'اسم الفرع/الوكالة'],
           ['key' => 'payment_method', 'label' => 'طريقة الدفع', 'format' => 'payment_method'],
            ['key' => 'payment_type', 'label' => 'وسيلة الدفع', 'format' => 'payment_type'],
           

            ['key' => 'receipt_number', 'label' => 'رقم السند'],
            ['key' => 'phone_number', 'label' => 'رقم الهاتف'],
            ['key' => 'depositor_name', 'label' => 'اسم المودع'],
            ['key' => 'commission', 'label' => 'العمولة', 'format' => 'money'],
            [
                'key' => 'actions',
                'label' => 'الإجراءات',
                'actions' => [
                    [
                        'type' => 'duplicate',
                        'label' => 'تكرار',
                        'icon' => 'fa fa-copy',
                        'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                        'showIf' => function($row) {
                            return $row->agency_id == auth()->user()->agency_id;
                        },
                    ],
                    // أضف هنا أي إجراء آخر بنفس showIf إذا أضفت إجراءات أخرى مستقبلاً
                ]
            ]
        ];
    }
} 