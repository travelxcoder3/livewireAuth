<?php

namespace App\Tables;

class ApprovalSequenceTable
{
    public static function columns()
    {
        return [
            ['key' => 'name', 'label' => 'اسم التسلسل'],
            ['key' => 'action_type', 'label' => 'نوع الإجراء'],
            [
                'key' => 'actions',
                'label' => 'الإجراءات',
                'actions' => [
                    [
                        'type' => 'view',
                        'label' => 'عرض التسلسل',
                        'icon' => 'fa fa-eye',
                        'method' => 'viewSequence',
                        'class' => 'text-[rgb(var(--primary-600))] border border-[rgb(var(--primary-600))] hover:bg-[rgba(var(--primary-100),0.5)] px-4 py-1 rounded-md text-xs font-medium transition',
                    ],
                    [
                        'type' => 'edit',
                        'label' => 'تعديل',
                        'icon' => 'fa fa-edit',
                        'method' => 'editSequence',
                        'class' => 'text-amber-600 border border-amber-600 hover:bg-amber-50 px-4 py-1 rounded-md text-xs font-medium transition',
                        'can' => auth()->user()->can('sequences.edit')
                    ]
                ]
            ]
        ];
    }
} 