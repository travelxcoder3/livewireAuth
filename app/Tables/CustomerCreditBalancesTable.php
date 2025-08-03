<?php

namespace App\Tables;

class CustomerCreditBalancesTable
{
    public static function columns(): array
    {
        return [
   
            ['key' => 'name', 'label' => 'اسم العميل'],
            ['key' => 'phone', 'label' => 'رقم الهاتف'],
            [
                'key' => 'credit_amount',
                'label' => 'الرصيد المستحق للعميل',
                'format' => 'money',
                'color' => fn($value) => $value > 0 ? 'green-600' : 'gray-500',
            ],
        ];
    }
}
