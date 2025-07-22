<?php

namespace App\Tables;

class ProviderTable
{
    public static function columns($user)
    {
        return [
            ['key' => 'service.label', 'label' => 'نوع الخدمة'],
            ['key' => 'name', 'label' => 'اسم المزود', 'class' => 'font-medium'],
            ['key' => 'type', 'label' => 'النوع', 'format' => 'badge'],
            ['key' => 'contact_info', 'label' => 'معلومات التواصل'],
            ['key' => 'status', 'label' => 'الحالة'],
            [
                'key' => 'actions',
                'label' => 'الإجراءات',
                'actions' => [
                    [
                        'type' => 'edit',
                        'label' => 'تعديل',
                        'icon' => 'fa fa-edit',
                        'method' => 'showEditModal',
                        'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                        'can' => $user->can('providers.edit')
                    ]
                ]
            ]
        ];
    }
} 