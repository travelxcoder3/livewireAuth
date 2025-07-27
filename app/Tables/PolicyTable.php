<?php

namespace App\Tables;

class PolicyTable
{
    public static function columns()
    {
        return [
            ['key' => 'index', 'label' => '#'],
            ['key' => 'title', 'label' => 'العنوان'],
            ['key' => 'content', 'label' => 'المحتوى', 'format' => 'limit'],
            [
                'key' => 'actions',
                'label' => 'الإجراءات',
                'actions' => [
                  [
                        'type' => 'edit',
                        'label' => 'تعديل',
                        'icon' => 'fa fa-edit',
                        'method' => 'edit',
                        'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold',
                    ],


                    [
                        'type' => 'delete',
                        'label' => 'حذف',
                        'icon' => 'fa fa-trash',
                        'method' => 'confirmDelete',
                        'class' => 'text-red-600 hover:text-red-800',
                    ]
                ]
            ]
        ];
    }
} 