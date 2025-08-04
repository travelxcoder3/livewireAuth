<?php
namespace App\Tables;

class CustomerAccountsTable
{
    public static function columns()
    {
        return [
            ['key' => 'name', 'label' => 'اسم العميل'],
            [
                'key' => 'account_type',
                'label' => 'نوع الحساب',
                'format' => function ($value) {
                    return match ($value) {
                        'individual' => 'فرد',
                        'company' => 'شركة',
                        'organization' => 'منظمة',
                        default => 'غير محدد',
                    };
                },
            ],
            [
                'key' => 'net_balance',
                'label' => 'الرصيد',
                'format' => function ($value, $row = null) {
                    // القيم من السطر الحالي
                    $remainingForCustomer = $row['remaining_for_customer'] ?? 0; // عليه
                    $remainingForCompany = $row['remaining_for_company'] ?? 0;  // له

                    return <<<HTML
            <div class="text-xs text-center leading-tight font-sans">
                <div><span class="text-green-600">له</span>&nbsp;&nbsp;<span class="text-red-600">عليه</span></div>
                <div><span class="text-green-600">{$remainingForCompany}</span>&nbsp;&nbsp;<span class="text-red-600">{$remainingForCustomer}</span></div>
            </div>
        HTML;
                },
                'html' => true,
            ],
            [
                'key' => 'total',
                'label' => 'الإجمالي',
                'format' => 'money',
                'color' => 'primary-600',
                'sortable' => true,
            ],
            [
                'key' => 'last_sale_date',
                'label' => 'تاريخ آخر عملية بيع',
                'format' => fn($value) => \Carbon\Carbon::parse($value)->format('Y-m-d'),
                'sortable' => true,
            ],
            ['key' => 'currency', 'label' => 'العملة'],
            [
                'key' => 'actions',
                'label' => 'الإجراء',
                'format' => 'custom',
                'buttons' => ['details']
            ]
        ];
    }
}
