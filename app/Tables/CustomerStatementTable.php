<?php
// app/Tables/CustomerTable.php
namespace App\Tables;

class CustomerStatementTable
{
    public static function columns(): array
    {
      return [
                ['key' => 'index', 'label' => '#', 'format' => 'serial', 'class' => 'w-12 text-center'],
                ['key' => 'name',  'label' => 'الاسم'],
                ['key' => 'phone', 'label' => 'الهاتف'],
                [
                    'key' => 'actions',
                    'label' => 'الإجراء',
                    'class' => 'w-32 text-center',
                    'actions' => [
                        [
                            'type'  => 'details',                 // سيبحث الجدول عن details_url في الصف
                            'label' => 'تفاصيل',
                            'icon'  => 'fas fa-eye',
                            'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                        ],
                    ],
                ],
            ];

}
}