<?php

namespace App\Tables;

class ApprovalSequenceTable
{
    public static function columns()
    {
        return [
            [
                'key' => 'name',
                'label' => 'اسم التسلسل',
                'format' => fn($value) => "
                    <div class='flex flex-col items-start'>
                        <span class='text-[rgb(var(--primary-700))] font-semibold'>$value</span>
                    </div>
                ",
                'html' => true,
            ],

            [
                'key' => 'action_type',
                'label' => 'نوع الإجراء',
                'format' => fn($value) => "
                    <div class='flex flex-col items-start'>
                        <span class='text-gray-800 font-medium'>$value</span>
                    </div>
                ",
                'html' => true,
            ],
            [
                'key' => 'actions',
                'label' => 'الإجراءات',
                'class' => 'text-center',
                'actions' => [
                    [
                        'type' => 'view',
                        'label' => 'عرض التسلسل',
                        'icon' => 'fa fa-eye',
                        'method' => 'viewSequence',
                        'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold',
                    ],
                    [
                        'type' => 'edit',
                        'label' => 'تعديل',
                        'icon' => 'fa fa-edit',
                        'method' => 'editSequence',
                        'can' => auth()->user()->can('sequences.edit'),
                        'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold',
                    ],
                ],
            ],
        ];
    }
}
