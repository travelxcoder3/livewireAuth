<?php

namespace App\Tables;

class EmployeeStatementTable
{
    public static function columns(): array
    {
        return [
            ['key'=>'index','label'=>'#','format'=>'serial','class'=>'w-12 text-center'],
            ['key'=>'name','label'=>'اسم الموظف'],
            ['key'=>'account_type_text','label'=>'نوع الحساب','class'=>'w-28'],
            [
                'key'   =>'balance_total',
                'label' =>'الصافي',
                'format'=>'number',
                'class' =>'w-28 text-left',
            ],
            ['key'=>'last_sale_date','label'=>'آخر عملية','class'=>'w-40'],
            ['key'=>'currency','label'=>'العملة','class'=>'w-20 text-center'],
            [
                'key'=>'actions','label'=>'الإجراء','class'=>'w-28 text-center',
                'actions'=>[
                    ['type'=>'details','label'=>'تفاصيل','icon'=>'fas fa-eye',
                     'class'=>'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]'],
                ],
            ],
        ];
    }
}
