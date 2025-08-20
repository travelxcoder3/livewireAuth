<?php
namespace App\Tables;

class SalesByBeneficiaryTable
{
    public static function columns(): array
    {
        return [
            [
                'key'   => 'beneficiary_name',
                'label' => 'المستفيد',
                'format'=> fn($v)=> "<span class='font-semibold text-gray-800'>".$v."</span>",
                'html'  => true,
                'class' => 'min-w-[160px]',
            ],
            [
                'key'   => 'status_html',
                'label' => 'الحالة',
            ],


            [
                'key'   => 'total',
                'label' => 'الإجمالي ',
                'format'=> fn($v)=> number_format((float)$v,2),
                'class' => 'text-right min-w-[120px]',
            ],
            [
                'key'   => 'collected',
                'label' => 'المحصل ',
                'format'=> fn($v)=> number_format((float)$v,2),
                'class' => 'text-right min-w-[120px]',
            ],
            [
                'key'   => 'count',
                'label' => 'عدد التحصيلات',
                'html'  => true,
                'format'=> fn($v)=> "<span class='px-2 py-1 rounded-full text-xs' style='background-color: rgba(var(--primary-100), 0.3); color: rgb(var(--primary-700));'>{$v}</span>",
            ],
            [
                'key'   => 'created_human',
                'label' => 'أُنشئت',
                'class' => 'text-gray-500',
            ],
            [
                'key'   => 'actions',
                'label' => 'إجراء',
                'class' => 'text-center min-w-[120px]',
                'actions' => [
                    [
                        'type'      => 'details',
                        'label'     => 'عرض التفاصيل',
                        'icon'      => 'fa fa-eye', // أيقونة عين
                        'method'    => 'showCollectionDetails',
                        'param_key' => 'id',
                        'class'     => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                    ],
                ],
            ],

        ];
    }
}
