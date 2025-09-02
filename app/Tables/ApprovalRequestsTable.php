<?php

namespace App\Tables;

class ApprovalRequestsTable
{
    public static function columns(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'format' => 'serial'],

            ['key' => 'item_name', 'label' => 'العنصر'],

            // النوع كنص عادي
            [
                'key'   => 'type_label',
                'label' => 'النوع',
                'format' => fn($value) => $value,
            ],

            ['key' => 'agency_branch', 'label' => 'الوكالة/الفرع'],
            ['key' => 'requested_by',  'label' => 'طُلب بواسطة'],

            // الحالة كنص عادي
            [
                'key'   => 'status',
                'label' => 'الحالة',
                'format' => function ($value) {
                    return match ($value) {
                        'pending'  => 'قيد الانتظار',
                        'approved' => 'موافَق عليه',
                        'rejected' => 'مرفوض',
                        default    => 'غير معروف',
                    };
                },
            ],

            ['key' => 'notes', 'label' => 'ملاحظات'],

            [
                'key'    => 'actions',
                'label'  => 'الإجراءات',
                'class'  => 'text-center',
                'actions'=> [
                    [
                        'type'      => 'approve',
                        'label'     => 'موافقة',
                        'icon'      => 'fa fa-check',
                        'can'       => true,
                        'class'     => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))] font-semibold',
                        'showIf'    => fn($row) => $row->status === 'pending',
                        'wireClick' => fn($row) => "approve({$row->_wire_id})",
                    ],
                    [
                        'type'      => 'reject',
                        'label'     => 'رفض',
                        'icon'      => 'fa fa-times',
                        'can'       => true,
                        'class'     => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))] font-semibold',
                        'showIf'    => fn($row) => $row->status === 'pending',
                        'wireClick' => fn($row) => "reject({$row->_wire_id})",
                    ],
                ],
            ],
        ];
    }
}