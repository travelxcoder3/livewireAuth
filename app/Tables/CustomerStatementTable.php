<?php
namespace App\Tables;

class CustomerStatementTable
{
    public static function columns(): array
    {
        return [
            ['key'=>'index','label'=>'#','format'=>'serial','class'=>'w-12 text-center'],
            ['key'=>'name','label'=>'اسم العميل'],
            ['key'=>'account_type_text','label'=>'نوع الحساب','class'=>'w-28'],
            [
                'key'   =>'balance_total',   // = remaining_for_customer - remaining_for_company
                'label' =>'الإجمالي',
                'format'=>'number',
                'class' =>'w-28 text-left',
            ],
            ['key'=>'last_sale_date','label'=>'تاريخ آخر عملية بيع','class'=>'w-40'],
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
