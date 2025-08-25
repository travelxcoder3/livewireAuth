<?php

namespace App\Tables;

class ProviderInvoiceTable
{
    public static function columns()
    {
        return [
            ['key' => 'name',           'label' => 'اسم المزوّد'],
            ['key' => 'service_labels', 'label' => 'نوع الخدمة'],
            ['key' => 'status',         'label' => 'الحالة'],

            [
                'key' => 'actions',
                'label' => 'الإجراء',
                'actions' => [
                    [
                        'type'   => 'custom',
                        'label'  => 'تفاصيل الفاتورة',
                        'icon'   => 'fas fa-file-invoice',
                        'method' => 'showInvoiceDetails',
                        'class'  => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                        'can'    => auth()->user()->can('invoicesDeteials.view'), 
                    ],
                ],
            ],
        ];
    }
}
