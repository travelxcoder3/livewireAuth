<?php

namespace App\Tables;

class EmployeeCollectionsByCustomerTable
{
    public static function columns(): array
    {
        return [
            [
                'key'   => 'customer_name',
                'label' => 'اسم العميل',
                'format' => fn($v) => "<span class='font-semibold text-gray-800'>$v</span>",
                'html'  => true,
                'class' => 'min-w-[160px]'
            ],
            [
                'key'   => 'debt_amount',
                'label' => 'الدين',
                'format' => fn($v) => "<span class='text-red-600 font-extrabold'>".number_format($v,2)."</span>",
                'html'  => true,
                'class' => 'text-right min-w-[120px]',
            ],
            [
                'key'   => 'last_paid',
                'label' => 'مبلغ آخر سداد',
                'format' => fn($v) => $v ? number_format($v,2) : '-',
            ],
            ['key'=>'last_paid_at','label'=>'تاريخ آخر سداد'],
            [
                'key'=>'debt_age_days',
                'label'=>'عمر الدين (يوم)',
                'format' => fn($v) => $v !== null ? (int)$v : '-',
                'html'=>true,
            ],
            ['key'=>'account_type','label'=>'نوع العميل'],
            ['key'=>'debt_type','label'=>'نوع المديونية'],
            ['key'=>'response','label'=>'تجاوب العميل'],
            ['key'=>'relation','label'=>'الارتباط'],

            // عمود الإجراءات
            [
                'key'   => 'actions',
                'label' => 'إجراء',
                'class' => 'text-center min-w-[120px]',
                'actions' => [
                    [
                        'type'   => 'pay',
                        'label'  => 'تسديد',
                        'icon'   => 'fas fa-hand-holding-usd', // أيقونة فلوس
                        'method' => 'openPay',
                        'param_key' => 'customer_id',
                        'class'  => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))] font-semibold text-xs',
                    ],
                ],
            ],

        ];
    }
}
