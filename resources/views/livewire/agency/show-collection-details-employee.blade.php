<div>

@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp
<div>
    <div class="space-y-6">

      
        <!-- العنوان الرئيسي -->
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold"
                style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-100), 0.5); padding-bottom: 0.5rem;">
                تفاصيل التحصيل
            </h2>
            <div class="flex justify-end mb-4">
                    <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('agency.employee-collections.all') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg border transition duration-200 text-sm font-medium
                            bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))]
                            hover:shadow-md hover:text-[rgb(var(--primary-700))]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform rotate-180" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>رجوع</span>
                    </a>


            </div>

        </div>

        <!-- المعلومات الأساسية -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold mb-4"
                style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-100), 0.5); padding-bottom: 0.5rem;">
                المعلومات الأساسية
            </h3>
           

            <div class="grid md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">اسم العميل:</span>
                    <span class="font-bold">{{  $sale->customer?->name ?? 'غير معروف' }}</span>

                   


                </div>
                <div>
                    <span class="text-gray-500">رقم الهاتف:</span>
                    <span class="font-bold">{{ $sale->customer?->phone ?? 'غير معروف' }}</span>
                </div>

                <div class="flex items-center">
                    <strong class="min-w-[100px] text-gray-500">نوع الحساب:</strong>
                        <span class="font-medium">{{ $accountTypeLabel }}</span>
                </div>
            </div>
        </div>

        <!-- المعلومات المالية -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold mb-4"
                style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-100), 0.5); padding-bottom: 0.5rem;">
                المعلومات المالية
            </h3>

            <div class="grid md:grid-cols-4 gap-4 text-sm">
                <div class="flex items-center">
                    <strong class="min-w-[100px] text-gray-500">نوع العميل:</strong>
                    <span
                        class="font-medium">{{ optional($sale->collections->last()?->customerType)->label ?? '-' }}</span>
                </div>
                
                    <div class="flex items-center">
                        <strong class="min-w-[100px] text-gray-500">نوع المديونية:</strong>
                        <span class="font-medium">{{ optional($sale->collections->last()?->debtType)->label ?? '-' }}</span>
                    </div>
                    <div class="flex items-center">
                        <strong class="min-w-[100px] text-gray-500">تجاوب العميل:</strong>
                        <span
                            class="font-medium">{{ optional($sale->collections->last()?->customerResponse)->label ?? '-' }}</span>
                    </div>
                    <div class="flex items-center">
                        <strong class="min-w-[100px] text-gray-500">نوع الارتباط:</strong>
                        <span
                            class="font-medium">{{ optional($sale->collections->last()?->customerRelation)->label ?? '-' }}</span>
                    </div>
            </div>
        </div>

        <!-- المبيعات الأخرى لنفس العميل -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold"
                style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-100), 0.5); padding-bottom: 0.5rem;">
                جميع المبيعات المرتبطة بالعميل
            </h3>

            <div class="flex items-center gap-3">
                <span class="border-b-2 border-[rgb(var(--primary-500))] 
                            text-[rgb(var(--primary-700))] text-xs font-semibold pb-0.5">
                    إجمالي المديونية:
                </span>
                <span class="border-b-2 border-red-500 text-red-600 font-bold text-sm pb-0.5">
                    {{ number_format($this->totalDebt, 2) }}
                </span>
            </div>

     </div>



  <x-toast />

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                    <thead class="bg-gray-100 text-gray-600">
                        <tr>
                            <th class="px-2 py-1">اسم المستفيد</th>
                            <th class="px-2 py-1">الخدمة</th>
                            <th class="px-2 py-1">تاريخ البيع</th>
                            <th class="px-2 py-1">مبلغ الخدمه </th>
                            <th class="px-2 py-1">المتبقي </th>
                            <th class="px-2 py-1">تاريخ السداد المتوقع</th>
                            <th class="px-2 py-1">الموظف</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        
                        @foreach ($customerSales as $s)
                            @php
                                $collected = $s->collections_total ?? 0;
                                $paidFromSale = $s->amount_paid ?? 0;
                                $totalPaid = $collected + $paidFromSale;
                                $remaining = ($s->usd_sell ?? 0) - $totalPaid;
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1">{{ $s->beneficiary_name ?? '-' }}</td>
                                <td class="px-2 py-1">{{ $s->service_type_name ?? '-' }}</td>

                                <td class="px-2 py-1">{{ $s->sale_date }}</td>
                                <td class="px-2 py-1 text-green-700 font-bold">{{ $s->usd_sell }}</td>

                                <td class="px-2 py-1 text-red-600 font-bold">
                                    {{ $remaining > 0 ? number_format($remaining, 2) : '-' }}
                                </td>

                                <td class="px-2 py-1">{{ $s->expected_payment_date ?? '-' }}</td>
                                <td class="px-2 py-1">{{ optional($s->employee)->name ?? '-' }}</td>
                             
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>


        <!-- تحصيل المبالغ -->
       <!-- تحصيل المبالغ لكل عميل عبر كل العمليات/المجموعات -->
@php
  // خريطة أسلوب التحصيل (إن وُجدت لديك نفس الأرقام)
  $collectorMap = [
    1=>'مباشر عبر المحصّل', 2=>'غير مباشر متابعة الموظف', 3=>'عبر الموظف مباشرة',
    4=>'متعثر مباشر', 5=>'متعثر غير مباشر', 6=>'شبه معدوم مباشر', 7=>'شبه معدوم غير مباشر',
  ];
@endphp

<div class="bg-white rounded-xl shadow-md p-6">
  <div class="flex justify-between items-center mb-4">
    <h3 class="text-lg font-bold"
        style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-100), .5); padding-bottom: .5rem;">
      تحصيل المبالغ
    </h3>
  </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
        <thead class="bg-gray-100 text-gray-600">
            <tr>
            <th class="px-2 py-1">التاريخ</th>
            <th class="px-2 py-1">المبلغ</th>
            <th class="px-2 py-1">أسلوب التحصيل</th>
            <th class="px-2 py-1">عن عملية</th>
            <th class="px-2 py-1">المحصّل</th>
            <th class="px-2 py-1">ملاحظات</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            @php $rows = $this->customerCollections; @endphp
            @forelse($rows as $col)
            <tr class="hover:bg-gray-50">
                <td class="px-2 py-1 whitespace-nowrap">{{ $col->payment_date }}</td>

                <td class="px-2 py-1 font-semibold" style="color: rgb(var(--primary-600));">
                {{ number_format($col->amount, 2) }}
                </td>

                <td class="px-2 py-1">
                {{ $collectorMap[$col->collector_method] ?? $col->collector_method ?? '-' }}
                </td>

                <td class="px-2 py-1">
                {{ optional($col->sale)->beneficiary_name ?? ('#'.optional($col->sale)->id) }}
                </td>

                <td class="px-2 py-1">
                {{ optional($col->user)->name ?? '-' }}
                </td>

               <td class="px-2 py-1">
                    @php
                            $raw = $col->note ?? '';

                            if (strpos($raw, 'سداد محفظة لمجموعة') !== false) {
                                $clean = 'سداد محفظة لمجموعة';
                            } else {
                                $clean = preg_replace([
                                    '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', // UUID
                                    '/\s*\([^)]*\d+[^)]*\)\s*/u',                                    // (سجل #…)
                                    '/مجموعة\s*#?\s*\d+/u',                                         // رقم المجموعة
                                ], ' ', $raw);

                                // حذف محارف التحكم والمحارف الخفية والرمز � تحديدًا
                                $clean = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{FFFD}]/u', '', $clean);
                                // حذف BOM إن وُجد
                                $clean = str_replace("\xEF\xBB\xBF", '', $clean);

                                // ترتيب المسافات
                                $clean = trim(preg_replace('/\s{2,}/', ' ', $clean));
                            }
                            @endphp
                            {{ $clean !== '' ? $clean : '-' }}
                    </td>



              
            </tr>
            @empty
            <tr>
                <td colspan="8" class="py-4 text-center text-gray-400">لا توجد عمليات تحصيل</td>
            </tr>
            @endforelse
        </tbody>
        </table>

        <div class="mt-3">
        {{ $rows->links() }}
        </div>
    </div>
    </div>


    </div>

    <style>
        /* تأثيرات hover للجدول */
        table tbody tr:hover {
            background-color: rgba(var(--primary-50), 0.5);
        }

        /* تأثيرات الأزرار */
        button[style*="background: linear-gradient"] {
            transition: all 0.3s ease;
        }

        button[style*="background: linear-gradient"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
            opacity: 0.9;
        }

        button[style*="background: linear-gradient"]:active {
            transform: translateY(0);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* تأثيرات النوافذ المنبثقة */
        .backdrop-blur-sm {
            backdrop-filter: blur(5px);
        }

        /* تأثيرات حقول الإدخال */
        input:focus,
        textarea:focus,
        select:focus {
            border-color: rgb(var(--primary-500));
            box-shadow: 0 0 0 2px rgba(var(--primary-500), 0.2);
            outline: none;
        }

        /* تدرجات الألوان للبطاقات */
        .bg-\[rgb\(var\(--primary-50\)\)\] {
            background-color: rgba(var(--primary-50), 1);
        }

        .border-\[rgb\(var\(--primary-100\)\)\] {
            border-color: rgba(var(--primary-100), 1);
        }
    </style>
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('amountsUpdated', () => {
                // لا حاجة لعمل أي شيء، البيانات سيتم تحديثها تلقائياً
                console.log('تم تحديث المبالغ بنجاح');
            });
        });
    </script>
</div>


    </div>