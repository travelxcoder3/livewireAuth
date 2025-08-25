<?php

namespace App\Tables;

class EmployeeFileTable
{
    public static function columns()
    {
        return [
            ['key' => 'serial', 'label' => '#', 'format' => 'serial'],
            ['key' => 'name', 'label' => 'الاسم'],
            ['key' => 'user_name', 'label' => 'اسم المستخدم'],
            ['key' => 'email', 'label' => 'البريد الإلكتروني'],
            ['key' => 'phone', 'label' => 'الهاتف'],
            ['key' => 'department.label','label' => 'القسم'],
            ['key' => 'position.label','label' => 'الوظيفة'],

            [
                'key' => 'actions',
                'label' => 'إجراءات',
                'actions' => [
                    [
                        'type'   => 'edit',           
                        'label'  => 'حساب الموظف',
                        'icon'   => 'fa fa-wallet',
                        'method' => 'openWallet',
                        'can'    => auth()->user()->can('employees.view'),
                        'showIf' => function ($row) {
                            return $row->agency_id == auth()->user()->agency_id;
                        },
                    ],
                ],
            ],
        ];
    }
}
