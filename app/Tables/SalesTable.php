<?php

namespace App\Tables;

class SalesTable
{
    /**
     * ترجع مصفوفة الأعمدة مع إمكانية التحكم في ظهور زر التكرار وزر PDF
     *
     * @param  bool  $hideDuplicate  إذا true يخفي زر "تكرار"
     * @param  bool  $showPdf        إذا true يظهر زر "PDF"
     * @return array
     */
    public static function columns(bool $hideDuplicate = false, bool $showPdf = false): array
    {
        // 1) تعريف جميع الأعمدة
        $columns = [
            ['key' => 'sale_date', 'label' => 'تاريخ البيع', 'format' => 'date'],
            ['key' => 'beneficiary_name', 'label' => 'اسم المستفيد'],
            ['key' => 'customer.name', 'label' => 'العميل'],
            ['key' => 'service.label', 'label' => 'الخدمة'],
            ['key' => 'provider.name', 'label' => 'المزود'],
            ['key' => 'service_date', 'label' => 'تاريخ الخدمة', 'format' => 'date'],
            ['key' => 'customer_via', 'label' => 'العميل عبر', 'format' => 'customer_via'],
            ['key' => 'usd_buy', 'label' => 'سعر المزود', 'format' => 'money', 'color' => 'primary-500'],
            ['key' => 'usd_sell', 'label' => 'السعر للعميل', 'format' => 'money', 'color' => 'primary-600'],
            ['key' => 'sale_profit', 'label' => 'الربح', 'format' => 'money', 'color' => 'primary-700'],
            ['key' => 'amount_paid', 'label' => 'المدفوع', 'format' => 'money'],
            [
                'key' => 'total_paid',
                'label' => 'المتحصل',
                'format' => 'money',
                'color' => fn($value) => $value > 0 ? 'green-600' : 'gray-500',
            ],
            [
                'key' => 'remaining_payment',
                'label' => 'الباقي',
                'format' => 'money',
                'color' => fn($value) => ($value ?? 0) > 0 ? 'red-600' : 'green-700',
            ],
            ['key' => 'expected_payment_date', 'label' => 'تاريخ السداد المتوقع', 'format' => 'date'],
            ['key' => 'reference', 'label' => 'المرجع'],
            ['key' => 'pnr', 'label' => 'PNR'],
            ['key' => 'route', 'label' => 'المسار/ التفاصيل'],
            ['key' => 'status', 'label' => 'الحالة', 'format' => 'status'],
            ['key' => 'agency.name', 'label' => 'اسم الفرع/الوكالة'],
            ['key' => 'payment_method', 'label' => 'حالة الدفع', 'format' => 'payment_method'],
            ['key' => 'payment_type', 'label' => 'وسيلة الدفع', 'format' => 'payment_type'],
            ['key' => 'receipt_number', 'label' => 'رقم السند'],
            ['key' => 'phone_number', 'label' => 'رقم هاتف المستفيد'],
            ['key' => 'depositor_name', 'label' => 'اسم المودع'],
            ['key' => 'commission', 'label' => 'عمولة العميل', 'format' => 'money'],
            ['key' => 'duplicatedBy.name', 'label' => 'تم التكرار بواسطة'],
            ['key' => 'user.name', 'label' => 'الموظف'],

        ];

        // 2) عمود الإجراءات
        $actionsColumn = [
            'key' => 'actions',
            'label' => 'الإجراءات',
            'format' => 'custom',
        ];

        // 3) إذا مطلوب عرض زر PDF فقط
        if ($showPdf) {
            $actionsColumn['buttons'] = ['pdf'];
        }

        // 4) إذا لم يتم طلب إخفاء زر التكرار
       if (!$hideDuplicate) {
            $actionsColumn['actions'] = [
                [
                    'type' => 'duplicate',
                    'label' => 'تكرار',
                    'icon' => 'fa fa-copy',
                    'can' => auth()->user()->can('sales.edit'),
                    'class' => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                    'showIf' => fn($row) => $row->agency_id == auth()->user()->agency_id,
                ],
                [
                    'type' => 'edit',
                    'label' => 'تعديل',
                    'icon' => 'fas fa-edit',
                    'can' => auth()->user()->can('sales.edit'),
                    'class' => 'text-[rgb(var(--primary-200))] hover:text-[rgb(var(--primary-500))]',
                    'showIf' => fn($row) => 
                        $row->agency_id == auth()->user()->agency_id &&
                        \Carbon\Carbon::parse($row->created_at)->diffInHours(now()) < 3,
                    'wireClick' => fn($row) => "edit({$row->id})",
                ],
            ];
}


        $columns[] = $actionsColumn;

        return $columns;
    }
    public static function customerFollowUpColumns(): array
    {
        return [
            ['key' => 'beneficiary_name', 'label' => 'اسم المستفيد'],
            ['key' => 'phone_number', 'label' => 'رقم هاتف المستفيد'],
            ['key' => 'customer.name', 'label' => 'العميل'],
            ['key' => 'service.label', 'label' => 'الخدمة'],
            ['key' => 'provider.name', 'label' => 'المزود'],
            ['key' => 'service_date', 'label' => 'تاريخ الخدمة', 'format' => 'date'],
            ['key' => 'status', 'label' => 'حالة العملية', 'format' => 'status'], // ✅ السطر المضاف
            [
                'key' => 'actions',
                'label' => 'الإجراءات',
                'format' => 'custom',
                'buttons' => ['pdf']
            ],
        ];
    }

}
