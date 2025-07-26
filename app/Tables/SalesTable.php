<?php

namespace App\Tables;

class SalesTable
{
    public static function columns()
    {
        // 1) أنشئ مصفوفة الأعمدة أولاً
        $columns = [
            ['key' => 'sale_date', 'label' => 'التاريخ', 'format' => 'date'],
            ['key' => 'beneficiary_name', 'label' => 'المستفيد'],
            ['key' => 'customer.name', 'label' => 'العميل'],
            ['key' => 'service.label', 'label' => 'الخدمة'],
            ['key' => 'provider.name', 'label' => 'المزود'],
            ['key' => 'service_date', 'label' => 'تاريخ الخدمة', 'format' => 'date'],
            ['key' => 'customer_via', 'label' => 'العميل عبر', 'format' => 'customer_via'],
            ['key' => 'usd_buy', 'label' => 'Buy', 'format' => 'money', 'color' => 'primary-500'],
            ['key' => 'usd_sell', 'label' => 'Sell', 'format' => 'money', 'color' => 'primary-600'],
            ['key' => 'sale_profit', 'label' => 'الربح', 'format' => 'money', 'color' => 'primary-700'],
            ['key' => 'amount_paid', 'label' => 'المبلغ المدفوع أثناء البيع', 'format' => 'money'],
            [
                'key'   => 'collections_sum_amount',
                'label' => 'إجمالي المبلغ المحصل',
                'format'=> 'money',
                'color' => function ($value) {
                    return $value > 0 ? 'green-600' : 'gray-500';
                },
            ],
            [
                'key'   => 'remaining_payment',
                'label' => 'المتبقي من المبلغ المحصل',
                'format'=> 'money',
                'color' => function ($value) {
                    return ($value ?? 0) > 0 ? 'red-600' : 'green-700';
                },
            ],
            ['key' => 'expected_payment_date', 'label' => 'تاريخ السداد المتوقع', 'format' => 'date'],
            ['key' => 'reference', 'label' => 'المرجع'],
            ['key' => 'pnr', 'label' => 'PNR'],
            ['key' => 'route', 'label' => 'Route'],
            ['key' => 'status', 'label' => 'الحالة', 'format' => 'status'],
            ['key' => 'user.name', 'label' => 'الموظف'],
            ['key' => 'agency.name', 'label' => 'اسم الفرع/الوكالة'],
            ['key' => 'payment_method', 'label' => 'حالة الدفع', 'format' => 'payment_method'],
            ['key' => 'payment_type', 'label' => 'وسيلة الدفع', 'format' => 'payment_type'],
            ['key' => 'receipt_number', 'label' => 'رقم السند'],
            ['key' => 'phone_number', 'label' => 'رقم الهاتف'],
            ['key' => 'depositor_name', 'label' => 'اسم المودع'],
            ['key' => 'commission', 'label' => ' مبلغ عمولة العميل', 'format' => 'money'],
            [
                'key'     => 'actions',
                'label'   => 'الإجراءات',
                'format'  => 'custom',
                'buttons' => ['pdf'],
                'actions' => [
                    [
                        'type'   => 'duplicate',
                        'label'  => 'تكرار',
                        'icon'   => 'fa fa-copy',
                        'class'  => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                        'showIf' => function ($row) {
                            return $row->agency_id === auth()->user()->agency_id;
                        },
                    ],
                    // ... إجراءات أخرى إذا لزم الأمر ...
                ],
            ],
        ];

        // 2) إذا كنا في صفحة تقارير المبيعات فقط، نحذف زر "تكرار"
        if (request()->is('agency/reports/sales')) {
            foreach ($columns as &$col) {
                if ($col['key'] === 'actions' && isset($col['actions'])) {
                    $col['actions'] = array_filter(
                        $col['actions'],
                        fn($action) => $action['type'] !== 'duplicate'
                    );
                }
            }
            unset($col);
        }

        // 3) نعيد المصفوفة بعد التصفية
        return $columns;
    }
}
