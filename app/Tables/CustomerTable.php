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
            ['key' => 'created_at', 'label' => 'تاريخ الإضافة', 'format' => 'date'],
            [
                'key' => 'actions',
                'label' => 'إجراءات',
                'actions' => [
                    [
                        'type' => 'edit',
                        'label' => 'تعديل',
                        'icon' => 'fa fa-edit',
                        'method' => 'edit',
                        'class' => 'text-blue-600 hover:text-blue-800',
                    ]
                ]
            ]
        ];
    }
} 