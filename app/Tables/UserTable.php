<?php

namespace App\Tables;

class UserTable
{
    public static function columns()
    {
        return [
            [
                'label' => 'الاسم',
                'field' => 'name',
                'class' => 'px-2 py-1 font-medium',
                'headerClass' => 'px-2 py-1',
            ],
            [
                'label' => 'البريد الإلكتروني',
                'field' => 'email',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
            ],
            [
                'label' => 'الدور',
                'field' => 'role_display',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
            ],
            [
                'label' => 'الحالة',
                'field' => 'status_display',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
                'type' => 'status',
            ],
            [
                'label' => 'الإجراءات',
                'field' => 'actions',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
                'type' => 'actions',
                'actions' => [
                    [
                        'label' => 'تعديل',
                        'method' => 'editUser',
                        'permission' => 'users.edit',
                        'class' => 'text-xs font-medium',
                        'color' => 'primary-600',
                    ],
                ],
            ],
        ];
    }
} 