<?php

namespace App\Tables;

class SalesTable
{
    public static function columns()
    {
        return [
            ['key' => 'sale_date', 'label' => 'التاريخ'],
            ['key' => 'beneficiary_name', 'label' => 'المستفيد'],
            ['key' => 'customer.name', 'label' => 'العميل'],
            ['key' => 'serviceType.name', 'label' => 'الخدمة'],
            ['key' => 'provider.name', 'label' => 'المزود'],
            ['key' => 'intermediary.name', 'label' => 'الوسيط'],
            ['key' => 'usd_buy', 'label' => 'Buy', 'format' => 'money', 'color' => 'primary-500'],
            ['key' => 'usd_sell', 'label' => 'Sell', 'format' => 'money', 'color' => 'primary-600'],
            ['key' => 'sale_profit', 'label' => 'الربح', 'format' => 'money', 'color' => 'primary-700'],
            ['key' => 'amount_paid', 'label' => 'المبلغ', 'format' => 'money'],
            ['key' => 'account.name', 'label' => 'الحساب'],
            ['key' => 'reference', 'label' => 'المرجع'],
            ['key' => 'pnr', 'label' => 'PNR'],
            ['key' => 'route', 'label' => 'Route'],
            ['key' => 'status', 'label' => 'الحالة', 'format' => 'status'],
            ['key' => 'user.name', 'label' => 'الموظف'],
            ['key' => 'agency.name', 'label' => 'اسم الفرع/الوكالة'],
            [
                'key' => 'actions',
                'label' => 'الإجراءات',
                'actions' => [
                    
                       
                    [
                        'type' => 'duplicate',
                        'label' => 'تكرار',
                        'icon' => 'fa fa-copy',
                        'class' => 'text-amber-600 hover:text-amber-800',
                        'showIf' => function($row) {
                            return $row->agency_id == auth()->user()->agency_id;
                        },
                    ]
                ]
            ]
        ];
    }
} 