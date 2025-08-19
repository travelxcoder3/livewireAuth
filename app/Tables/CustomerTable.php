<?php

namespace App\Tables;

class CustomerTable
{
    public static function columns()
    {
        return [
            ['key' => 'name', 'label' => 'الاسم'],
            ['key' => 'email', 'label' => 'البريد'],
            ['key' => 'phone', 'label' => 'الهاتف'],
            ['key' => 'address', 'label' => 'العنوان'],
            [
                'key' => 'has_commission',
                'label' => 'يستحق عمولة',
                'format' => fn($value) => $value == 1 ? 'نعم' : 'لا',
            ],
            ['key' => 'created_at', 'label' => 'تاريخ الإضافة', 'format' => 'date'],

            [
                'key' => 'account_type',
                'label' => 'نوع الحساب',
                'format' => fn($value) => match ($value) {
                    'individual' => 'individual-فرد',
                    'company' => 'company-شركة',
                    'organization' => ' organization-منظمة',
                    default => '-',
                },
            ],

            [
                'key' => 'images',
                'label' => 'الصور',
                'format' => function ($value) {
    return view('components.customer-image-preview', ['images' => $value])->render();
},

            ],


           
            [
            'key' => 'actions',
            'label' => 'إجراءات',
            'actions' => [
                [
                'type' => 'edit',
                'label' => 'تعديل',
                'icon' => 'fa fa-edit',
                'can' => auth()->user()->can('customers.edit'),
                'method' => 'edit',
                'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold',
                ],
                [
                'type'   => 'custom',
                'label'  => 'رصيد',
                'icon'   => 'fa fa-wallet',
                'method' => 'openWallet',
                 'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold',
                ],
            ]
            ]

        ];
    }
} 