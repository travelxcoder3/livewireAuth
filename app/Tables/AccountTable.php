<?php

namespace App\Tables;

class AccountTable
{
    public static function columns()
    {
        return [
            ['key' => 'created_at', 'label' => 'التاريخ', 'format' => 'date', 'sortable' => true],
            ['key' => 'beneficiary_name', 'label' => 'اسم المستفيد'],
            ['key' => 'service.label', 'label' => 'الخدمة'],
            ['key' => 'route', 'label' => 'المسار'],
            ['key' => 'pnr', 'label' => 'PNR'],
            ['key' => 'reference', 'label' => 'المرجع'],
            ['key' => 'status', 'label' => 'الحدث', 'format' => 'status'],
            ['key' => 'usd_sell', 'label' => 'سعر البيع', 'format' => 'money', 'color' => 'primary-600', 'sortable' => true],
            ['key' => 'usd_buy', 'label' => 'سعر الشراء', 'format' => 'money', 'color' => 'primary-500', 'sortable' => true],
            ['key' => 'provider.name', 'label' => 'المزود'],
            ['key' => 'customer.name', 'label' => 'العميل'],
            ['key' => 'agency.name', 'label' => 'اسم الفرع/الوكالة'],
        ];
    }
} 