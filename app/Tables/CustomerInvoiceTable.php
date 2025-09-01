<?php

namespace App\Tables;

class CustomerInvoiceTable
{
    public static function columns()
    {
        return [
            [
                'key' => 'name',
                'label' => 'اسم العميل',
            ],
             [
                'key' => 'phone',
                'label' => 'رقم الهاتف',
            ],
            [
                'key' => 'actions',
                'label' => 'الإجراء',
                'actions' => [
                    [
                        'type' => 'custom',
                        'label' => 'تفاصيل الفاتورة',
                        'icon' => 'fas fa-file-invoice',
                        'method' => 'showInvoiceDetails',
                        'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                    ]
                ]
            ],
        ];
    }
}
