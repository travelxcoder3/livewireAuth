<?php

namespace App\Tables;

class InvoiceTable
{
    public static function columns(): array
    {
        return [
            ['key'=>'serial',         'label'=>'#',            'format'=>'serial'],
            ['key'=>'invoice_number', 'label'=>'رقم الفاتورة'],
            ['key'=>'date',           'label'=>'التاريخ',      'format'=>'date'],
            ['key'=>'entity_name',    'label'=>'الجهة'],

            ['key'=>'sales_count',    'label'=>'عدد العمليات', 'format'=>'int'],

           
            ['key'=>'subtotal',       'label'=>'الصافي',       'format'=>'money'],
            ['key'=>'tax_total',      'label'=>'الضريبة',      'format'=>'money'],
            ['key'=>'grand_total',    'label'=>'الإجمالي',     'format'=>'money'],

            ['key'=>'user.name',      'label'=>'المُصدر'],

            [
                'key'=>'actions', 'label'=>'الإجراء',
                'actions'=>[
                    [
                        'label'=>'تفاصيل','icon'=>'fas fa-eye',
                        'method'=>'openDetails',
                        'class'=>'text-[rgb(var(--primary-600))] hover:text-black font-medium text-xs',
                    ],
                    [
                        'label'=>'PDF','icon'=>'fas fa-file-pdf',
                        'method'=>'downloadPdf',
                        'class'=>'text-[rgb(var(--primary-600))] hover:text-black font-medium text-xs',
                    ],
                ],
            ],
        ];
    }
}
