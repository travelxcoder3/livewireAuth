<?php

namespace App\Tables;

class ServiceTypeTable
{
    public static function columns()
    {
        return [
            [
                'label' => '#',
                'field' => 'iteration',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
                'type' => 'index',
            ],
            [
                'label' => 'اسم نوع الخدمة',
                'field' => 'name',
                'class' => 'px-2 py-1 font-medium',
                'headerClass' => 'px-2 py-1',
            ],
            [
                'label' => 'الإجراءات',
                'field' => 'actions',
                'class' => 'px-2 py-1 whitespace-nowrap',
                'headerClass' => 'px-2 py-1',
                'type' => 'actions',
                'actions' => [
                    [
                        'label' => 'تعديل',
                        'method' => 'showEditModal',
                        'permission' => 'service_types.edit',
                        'class' => 'font-medium text-xs mx-1',
                        'color' => 'primary-600',
                    ],
                ],
            ],
        ];
    }
} 