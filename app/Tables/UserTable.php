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
                'label' => 'اسم الفرع',
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
                'key' => 'sales_target',
                'label' => 'الهدف المبيعي',
                'format' => function ($value) {
                    return number_format($value, 2); 
                }
            ],

            [
                'key' => 'main_target',
                'label' => 'الهدف الأساسي',
                'format' => function ($value) {
                    return number_format($value, 2); 
                }
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
                        'icon' => 'fas fa-edit',
                        'method' => 'editUser',
                        'permission' => 'users.edit',
                        'can' => auth()->user()->can('users.edit'),
                        'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
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