<?php

namespace App\Tables;

class QuotationsReportTable
{
    public static function columns(): array
    {
        return [
            ['key' => 'index',            'label' => '#',              'format' => 'serial',  'class' => 'w-12 text-center'],
            ['key' => 'quotation_number', 'label' => 'رقم العرض'],
            ['key' => 'quotation_date',   'label' => 'التاريخ',        'format' => 'date'],
            ['key' => 'to_client',        'label' => 'العميل'],
            ['key' => 'currency',         'label' => 'العملة',         'class' => 'w-16'],
            ['key' => 'tax_rate',         'label' => 'نسبة الضريبة%',  'format' => 'percent'],
            ['key' => 'subtotal',         'label' => 'الإجمالي الفرعي','format' => 'money'],
            ['key' => 'tax_amount',       'label' => 'الضريبة',        'format' => 'money'],
            ['key' => 'grand_total',      'label' => 'الإجمالي الكلي', 'format' => 'money', 'class' => 'font-semibold'],
            ['key' => 'user.name',        'label' => 'المستخدم'],
            ['key' => 'items_count',      'label' => 'عدد البنود',     'class' => 'text-center w-16'],
            [
                'key' => 'actions',
                'label' => 'الإجراء',
                'class' => 'w-32 text-center',
                'actions' => [
                  ['type' => 'pdf',  'label' => 'PDF',  'icon' => 'fas fa-file-pdf'],
                  ['type' => 'view', 'label' => 'عرض',  'icon' => 'fas fa-eye']

                ],
            ],
        ];
    }
}
