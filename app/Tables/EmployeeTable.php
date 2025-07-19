<?php

namespace App\Tables;

use App\Support\Column;

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
                        'can' => auth()->user()->can('employees.edit'),
                        'showIf' => function ($row) {
                            return $row->agency_id == auth()->user()->agency_id;
                        },
                    ]
                ]
            ]
        ];
    }
}
