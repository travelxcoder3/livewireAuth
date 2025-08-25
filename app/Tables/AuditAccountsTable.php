<?php

namespace App\Tables;

class AuditAccountsTable
{
    public static function groupedColumns(): array
    {
        return [
            ['key' => 'key_label',  'label' => 'المفتاح'],
            ['key' => 'row_count',  'label' => 'العدد'],
            ['key' => 'total',      'label' => 'المبيعات'],
            ['key' => 'cost',       'label' => 'التكلفة'],
            ['key' => 'commission', 'label' => 'عمولة العميل'],
            ['key' => 'net_profit', 'label' => 'الربح الصافي'],
        ];
    }

    public static function detailColumns(): array
    {
        return [
            ['key' => 'serial',          'label' => '#', 'format' => 'serial'],
            ['key' => 'sale_date',       'label' => 'التاريخ', 'format' => 'date'],
            ['key' => 'service.label',   'label' => 'الخدمة'],
            ['key' => 'customer.name',   'label' => 'العميل'],
            ['key' => 'provider.name',   'label' => 'المزوّد'],
            ['key' => 'total',           'label' => 'المبيعات'],
            ['key' => 'cost',            'label' => 'التكلفة'],
            ['key' => 'commission',      'label' => 'عمولة'],
            ['key' => 'net_profit',      'label' => 'الربح الصافي'],
        ];
    }
}
