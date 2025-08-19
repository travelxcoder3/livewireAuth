<?php

namespace App\Tables;

use App\Support\Column;

class EmployeeTable
{
    public static function columns()
    {
        return [
            ['key' => 'serial', 'label' => '#', 'format' => 'serial'],

            ['key' => 'name', 'label' => 'الاسم'],
            ['key' => 'user_name', 'label' => 'اسم المستخدم'],
            ['key' => 'email', 'label' => 'البريد الإلكتروني'],
            ['key' => 'phone', 'label' => 'الهاتف'],
            ['key' => 'department.label','label' => 'القسم',],
            ['key' => 'position.label','label' => 'الوظيفة',],

            ['key' => 'created_at', 'label' => 'تاريخ الإنشاء', 'format' => 'date'],

            [
                'key' => 'actions',
                'label' => 'إجراءات',
                'actions' => [
                    [
                        'type' => 'edit',
                        'label' => 'تعديل',
                        'icon' => 'fa fa-edit',
                        'method' => 'editEmployee',
                        'showIf' => function ($row) {
                            return $row->agency_id == auth()->user()->agency_id;
                        },
                    ],
                    [
                        // يمكنك إبقاء type = 'edit' لو الـ DataTable ينسّق حسب الـ type،
                        // أو استعمل 'button' إن كان مدعومًا في مشروعك.
                        'type' => 'edit',
                        'label' => 'حساب الموظف',
                        'icon' => 'fa fa-wallet',
                        'method' => 'openWallet',   // تستدعي method من EmployeeIndex
                        'can' => auth()->user()->can('employees.view'), // غيّر الصلاحية لو لزم
                        'showIf' => function ($row) {
                            return $row->agency_id == auth()->user()->agency_id;
                        },
                    ],
                ]
            ]

        ];
    }
}
