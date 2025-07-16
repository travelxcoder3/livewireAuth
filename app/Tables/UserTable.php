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
                'key' => 'name',
                'class' => 'px-2 py-1 font-medium',
                'headerClass' => 'px-2 py-1',
            ],
            [
                'label' => 'البريد الإلكتروني',
                'field' => 'email',
                'key' => 'email',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
            ],
            [
                'label' => 'الوكالة/الفرع',
                'field' => 'agency_name',
                'key' => 'agency_name',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
            ],
            [
                'label' => 'الدور',
                'field' => 'role_display',
                'key' => 'role_display',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
            ],
            [
                'label' => 'الحالة',
                'field' => 'status_display',
                'key' => 'status_display',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
                'type' => 'status',
            ],
            [
                'label' => 'الإجراءات',
                'field' => 'actions',
                'key' => 'actions',
                'class' => 'px-2 py-1',
                'headerClass' => 'px-2 py-1',
                'type' => 'actions',
                'actions' => [
                    [
                        'label' => 'تعديل',
                        'method' => 'editUser',
                        'permission' => 'users.edit',
                        'class' => 'text-xs font-medium',
                        'showIf' => function($user) {
                            return $user->agency_id == auth()->user()->agency_id;
                        },
                    ],
                    // يمكنك إضافة زر حذف بنفس الشرط إذا كان موجودًا
                ],
            ],
        ];
    }
} 