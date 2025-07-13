<?php

namespace App\Tables;

class EmployeeTable
{
    public static function columns()
    {
        return [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'name', 'label' => 'الاسم'],
            ['key' => 'user_name', 'label' => 'اسم المستخدم'],
            ['key' => 'email', 'label' => 'البريد الإلكتروني'],
            ['key' => 'phone', 'label' => 'الهاتف'],
            ['key' => 'branch', 'label' => 'الفرع'],
            ['key' => 'department.name', 'label' => 'القسم'],
            ['key' => 'position.name', 'label' => 'الوظيفة'],
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
                        'can' => auth()->user()->can('employees.edit')
                    ]
                ]
            ]
        ];
    }
} 