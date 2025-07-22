<?php
// app/Tables/ObligationTable.php

namespace App\Tables;

use Illuminate\Support\Str;

class ObligationTable
{
    public static function columns(): array
    {
        return [
            ['key' => 'title',       'label' => 'العنوان'],
            ['key' => 'description', 'label' => 'الوصف', 'format' => fn($v)=> Str::limit($v,30)],
            ['key' => 'start_date',  'label' => 'تاريخ البدء', 
             'format' => fn($v)=> $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d') : '-'],
            ['key' => 'end_date',    'label' => 'تاريخ الانتهاء', 
             'format' => fn($v)=> $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d') : '-'],
            ['key' => 'for_all',     'label' => 'لكل الموظفين', 
             'format' => fn($v)=> $v ? 'نعم' : 'لا'],
             ['key'=>'users','label'=>'الموظفون','format'=>fn($v)=> $v->pluck('name')],

            [
                'key'     => 'actions',
                'label'   => 'الإجراءات',
                'actions' => [
                    [
                        'type'  => 'edit',
                        'label' => 'تعديل',
                        'icon'  => 'fas fa-edit',
                        'method'=> 'edit',
                    ],
                    [
                        'type'   => 'delete',
                        'label'  => 'حذف',
                        'icon'   => 'fas fa-trash',
                        'method' => 'delete',
                        'confirm'=> true,
                        'class'  => 'text-red-600 hover:text-red-800',
                    ],
                ],
            ],
        ];
    }
}
