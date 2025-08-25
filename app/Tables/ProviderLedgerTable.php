<?php

namespace App\Tables;

class ProviderLedgerTable
{
    public static function columns(): array
    {
        return [
            ['key' => 'serial',         'label' => '#',          'format' => 'serial'],
            ['key' => 'provider_name',  'label' => 'اسم المزوّد'],
            ['key' => 'service_label',  'label' => 'الخدمة'],
            ['key' => 'tx_count',       'label' => 'عدد العمليات', 'format' => fn($v) => (int) $v],

            ['key' => 'for_provider',   'label' => 'له',    'format' => 'money',
                'color' => fn($v) => $v > 0 ? 'green-600' : 'gray-500'],

            ['key' => 'for_agency',     'label' => 'عليه', 'format' => 'money',
                'color' => fn($v) => $v > 0 ? 'red-600'   : 'gray-500'],

            ['key' => 'net',            'label' => 'الرصيد','format' => 'money',
                'color' => fn($v) => $v > 0 ? 'green-700' : ($v < 0 ? 'red-700' : 'gray-600')],
        ];
    }
}
