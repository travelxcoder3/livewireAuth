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
        // 1) الأعمدة
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
                'key'   => 'total_paid',
                'label' => 'المتحصل',
                'format'=> 'money',
                'color' => fn($value) => $value > 0 ? 'green-600' : 'gray-500',
            ],
            [
                'key'   => 'remaining_payment',
                'label' => 'الباقي',
                'format'=> 'money',
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
            'key'   => 'actions',
            'label' => 'الإجراءات',
            'format'=> 'custom',
        ];

        // 3) زر PDF فقط إن لزم
        if ($showPdf) {
            $actionsColumn['buttons'] = ['pdf'];
        }

        // 4) أزرار (تكرار/تعديل/طلب تعديل) + شارة انتظار
        if (!$hideDuplicate) {
            $actionsColumn['actions'] = [
                // تكرار
                [
                    'type'      => 'duplicate',
                    'label'     => 'تكرار',
                    'icon'      => 'fa fa-copy',
                    'can'       => auth()->user()->can('sales.edit'),
                    'class'     => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                    'showIf'    => fn($row) => $row->agency_id == auth()->user()->agency_id,
                    'wireClick' => fn($row) => "duplicate({$row->id})",
                ],

                // تعديل: داخل نافذة الإنشاء أو خلال نافذة من آخر موافقة "حديثة" + لا يوجد طلب pending
                [
                    'type'      => 'edit',
                    'label'     => 'تعديل',
                    'icon'      => 'fas fa-edit',
                    'can'       => auth()->user()->can('sales.edit'),
                    'class'     => 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]',
                    'showIf'    => function ($row) {
                        $user = auth()->user();
                        if (!$user || $row->agency_id != $user->agency_id) return false;

                        $winMins = (int) ($user->agency?->editSaleWindowMinutes() ?? 180);
                        $winMins = max(0, $winMins);

                        // pending؟
                        $hasPending = \App\Models\ApprovalRequest::where('model_type', \App\Models\Sale::class)
                            ->where('model_id', $row->id)
                            ->when(\Illuminate\Support\Facades\Schema::hasColumn('approval_requests', 'agency_id'), function ($q) use ($user) {
                                $q->where('agency_id', $user->agency_id);
                            })
                            ->where('status', 'pending')
                            ->exists();
                        if ($hasPending) return false;

                        // داخل نافذة الإنشاء؟
                        $ageInMinutes = \Carbon\Carbon::parse($row->created_at)->diffInMinutes(now());
                        if ($winMins > 0 && $ageInMinutes < $winMins) return true;

                        // موافقة "حديثة" خلال نفس النافذة؟
                        $hasRecentApproval = \App\Models\ApprovalRequest::where('model_type', \App\Models\Sale::class)
                            ->where('model_id', $row->id)
                            ->when(\Illuminate\Support\Facades\Schema::hasColumn('approval_requests', 'agency_id'), function ($q) use ($user) {
                                $q->where('agency_id', $user->agency_id);
                            })
                            ->where('status', 'approved')
                            ->where('created_at', '>=', now()->subMinutes($winMins))
                            ->exists();

                        return $hasRecentApproval;
                    },
                    'wireClick' => fn($row) => "edit({$row->id})",
                ],

                // طلب تعديل: خارج النافذة ولا يوجد pending ولا موافقة حديثة
                [
                    'type'      => 'request_edit',
                    'label'     => 'طلب تعديل',
                    'icon'      => 'fas fa-paper-plane',
                    'can'       => auth()->user()->can('sales.edit'),
                    'class'     => 'text-amber-600 hover:text-amber-700',
                    'showIf'    => function ($row) {
                        $user = auth()->user();
                        if (!$user || $row->agency_id != $user->agency_id) return false;

                        $winMins = (int) ($user->agency?->editSaleWindowMinutes() ?? 180);
                        $winMins = max(0, $winMins);

                        $hasPending = \App\Models\ApprovalRequest::where('model_type', \App\Models\Sale::class)
                            ->where('model_id', $row->id)
                            ->when(\Illuminate\Support\Facades\Schema::hasColumn('approval_requests', 'agency_id'), function ($q) use ($user) {
                                $q->where('agency_id', $user->agency_id);
                            })
                            ->where('status', 'pending')
                            ->exists();
                        if ($hasPending) return false;

                        $ageInMinutes = \Carbon\Carbon::parse($row->created_at)->diffInMinutes(now());
                        $insideCreationWindow = ($winMins > 0 && $ageInMinutes < $winMins);
                        if ($insideCreationWindow) return false;

                        $hasRecentApproval = \App\Models\ApprovalRequest::where('model_type', \App\Models\Sale::class)
                            ->where('model_id', $row->id)
                            ->when(\Illuminate\Support\Facades\Schema::hasColumn('approval_requests', 'agency_id'), function ($q) use ($user) {
                                $q->where('agency_id', $user->agency_id);
                            })
                            ->where('status', 'approved')
                            ->where('created_at', '>=', now()->subMinutes($winMins))
                            ->exists();

                        return !$hasRecentApproval;
                    },
                    'wireClick' => fn($row) => "requestEdit({$row->id})",
                ],

                // شارة "بانتظار الموافقة"
                [
                    'type'   => 'pending_badge',
                    'label'  => 'بانتظار الموافقة',
                    'icon'   => 'fa fa-hourglass-half',
                    'class'  => 'text-gray-400 cursor-not-allowed',
                    'showIf' => function ($row) {
                        $user = auth()->user();
                        return \App\Models\ApprovalRequest::where('model_type', \App\Models\Sale::class)
                            ->where('model_id', $row->id)
                            ->when(\Illuminate\Support\Facades\Schema::hasColumn('approval_requests', 'agency_id'), function ($q) use ($user) {
                                $q->where('agency_id', $user->agency_id);
                            })
                            ->where('status', 'pending')
                            ->exists();
                    },
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
            ['key' => 'status', 'label' => 'حالة العملية', 'format' => 'status'],
            [
                'key'    => 'actions',
                'label'  => 'الإجراءات',
                'format' => 'custom',
                'buttons'=> ['pdf']
            ],
        ];
    }
}
