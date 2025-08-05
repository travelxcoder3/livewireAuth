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
                <a href="{{ route('agency.collections') }}"
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
                    <strong class="min-w-[100px] text-gray-500">الحالة:</strong>
                    <span
                        class="font-medium">{{ optional($sale->collections->last()?->customerType)->label ?? 'غير محدد' }}</span>
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
                @can('collection.details.view')
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
                @endcan
            </div>
        </div>

        <!-- المبيعات الأخرى لنفس العميل -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold mb-4"
                style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-100), 0.5); padding-bottom: 0.5rem;">
                جميع المبيعات المرتبطة بالعميل
            </h3>
 @if($availableBalanceToPayOthers > 0)
    <div class="flex justify-between items-center mb-4">
        <div></div> {{-- حجز مكان --}}
        <div class="flex items-center gap-4">
            <span class="text-sm font-bold text-[rgb(var(--primary-700))]">
                رصيد العميل لدى الشركة: 
                <span class="text-green-700">{{ number_format($availableBalanceToPayOthers, 2) }}</span>
            </span>

            <x-primary-button
                wire:click="openPayToOthersModal"
                padding="px-4 py-2"
                fontSize="text-sm"
                class="font-bold"
            >
                تسديد للعملاء
            </x-primary-button>

        </div>
    </div>
@endif

  <x-toast />

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                    <thead class="bg-gray-100 text-gray-600">
                        <tr>
                            <th class="px-2 py-1">اسم المستفيد</th>
                            <th class="px-2 py-1">الخدمة</th>
                            <th class="px-2 py-1">تاريخ البيع</th>
                            <th class="px-2 py-1">المبلغ المحصل</th>
                            <th class="px-2 py-1">تحت التحصيل</th>
                            <th class="px-2 py-1">تاريخ السداد المتوقع</th>
                            <th class="px-2 py-1">الموظف</th>
                            <th class="px-2 py-1">الإجراء</th>
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
                                <td class="px-2 py-1 text-green-700 font-bold">{{ number_format($totalPaid, 2) }}</td>
 <td class="px-2 py-1 text-red-600 font-bold">
    {{ $remaining > 0 ? number_format($remaining, 2) : '-' }}
</td>

                                <td class="px-2 py-1">{{ $s->expected_payment_date ?? '-' }}</td>
                                <td class="px-2 py-1">{{ optional($s->employee)->name ?? '-' }}</td>
                                <td class="px-2 py-1">
                                    <x-primary-button wire:click="openEditAmountModal({{ $s->id }})" padding="px-2 py-1"
                                        class="text-xs">
                                        سداد
                                    </x-primary-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>


        <!-- تحصيل المبالغ -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold"
                    style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-100), 0.5); padding-bottom: 0.5rem;">
                    تحصيل المبالغ
                </h3>

            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                    <thead class="bg-gray-100 text-gray-600">
                        <tr>
                            <th class="px-2 py-1">تاريخ التحصيل</th>
                            <th class="px-2 py-1">المبلغ المحصل</th>
                            <th class="px-2 py-1">المحصّل</th>
                            <th class="px-2 py-1">الملاحظات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($sale->collections as $col)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1 whitespace-nowrap">{{ $col->payment_date }}</td>
                                <td class="px-2 py-1" style="color: rgb(var(--primary-600)); font-weight: 600;">
                                    {{ number_format($col->amount, 2) }}</td>
                                <td class="px-2 py-1">--</td>
                                <td class="px-2 py-1">{{ $col->note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-gray-400">لا توجد عمليات تحصيل</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- ملخص الحساب -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-6 text-sm">
                <div class="bg-[rgb(var(--primary-50))] p-3 rounded-lg border border-[rgb(var(--primary-100))]">
                    <strong class="block text-[rgb(var(--primary-700))] mb-1">قيمة الفاتورة:</strong>
                    <span class="font-bold"
                        style="color: rgb(var(--primary-600));">{{ number_format($sale->usd_sell, 2) }}</span>
                </div>
                <div class="bg-[rgb(var(--primary-50))] p-3 rounded-lg border border-[rgb(var(--primary-100))]">
                    <strong class="block text-[rgb(var(--primary-700))] mb-1">المدفوع:</strong>
                    <span class="font-bold"
                        style="color: rgb(var(--primary-600));">{{ number_format($amountReceived, 2) }}</span>
                </div>
                <div class="bg-[rgb(var(--primary-50))] p-3 rounded-lg border border-[rgb(var(--primary-100))]">
                    <strong class="block text-[rgb(var(--primary-700))] mb-1">المتبقي:</strong>
                    <span class="font-bold"
                        style="color: rgb(var(--primary-600));">{{ number_format($remainingAmount, 2) }}</span>
                </div>
                <div class="bg-[rgb(var(--primary-50))] p-3 rounded-lg border border-[rgb(var(--primary-100))]">
                    <strong class="block text-[rgb(var(--primary-700))] mb-1">عمر الدين:</strong>
                    @php
                        use Illuminate\Support\Carbon;
                        $debtAge = round(Carbon::parse($sale->sale_date)->diffInDays(now(), false));
                    @endphp
                    <span class="font-bold" style="color: rgb(var(--primary-600));">
                        @if ($debtAge < 0)
                            لم يبدأ بعد
                        @elseif ($debtAge === 0)
                            اليوم
                        @else
                            {{ $debtAge }} يوم
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- نافذة تعديل المبلغ -->
        @if ($showEditModal)
            <div class="fixed inset-0 z-50 bg-black/10 flex items-start justify-center pt-10 backdrop-blur-sm">
                <div
                    class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
                    <button wire:click="cancelEdit"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                        &times;
                    </button>

                    <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                        تعديل المبلغ
                    </h3>

                    <!-- رسالة تنبيه عند عدم وجود مبلغ متبقي -->
                    @if ($isPayToOthersMode)
    <div class="mb-4 px-4 py-2 rounded-lg text-xs bg-blue-50 border border-blue-200 text-blue-700">
        سيتم استخدام رصيد الشركة لدى العميل لتسديد مديونية المستفيدين الآخرين.
    </div>
@elseif ($remainingAmount <= 0)
                        <div class="mb-4 px-4 py-2 rounded-lg text-xs"
                            style="background-color: rgba(var(--primary-100), 0.5); border: 1px solid rgba(var(--primary-200), 0.5); color: rgb(var(--primary-700));">
                            تم سداد كامل المبلغ، لا يمكن التحصيل.
                        </div>
                    @endif

                    <!-- رسائل الأخطاء -->
                    @if ($errors->has('amount'))
                        <div class="mb-4 px-4 py-2 rounded-lg text-xs bg-red-100 border border-red-300 text-red-700">
                            {{ $errors->first('amount') }}
                        </div>
                    @endif
                @if($isPayToOthersMode)
                    <div class="text-sm mb-4">
                <x-select-field
                    label="اختر المستفيد"
                    :options="collect($payToCustomerList)->mapWithKeys(fn($item) => [$item['id'] => $item['name']])->toArray()"
                    wireModel="selectedPayCustomerId"
                    placeholder="اختر مستفيداً"
                    name="selectedPayCustomerId"
                />

                    </div>
                @endif

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- إجمالي الفاتورة -->
                        <div class="text-sm">
                            <strong class="text-[rgb(var(--primary-700))]">إجمالي الفاتورة:</strong>
                            <input type="text" readonly
    class="w-full mt-1 border rounded-lg px-3 py-2 bg-[rgb(var(--primary-50))] text-right font-bold"
    value="{{ number_format($totalAmount, 2) }}"
    @if($isPayToOthersMode && !$selectedPayCustomerId) disabled @endif>


                        </div>

                        <!-- المدفوع من المبيعات -->
                        <div class="text-sm">
                            <strong class="text-[rgb(var(--primary-700))]">مدفوع من المبيعات:</strong>
                            <input type="text" readonly
                                class="w-full mt-1 border rounded-lg px-3 py-2 bg-[rgb(var(--primary-50))] text-right font-bold"
                                value="{{ number_format($paidFromSales, 2) }}">
                        </div>

                        <!-- المدفوع من التحصيل -->
                        <div class="text-sm">
                            <strong class="text-[rgb(var(--primary-700))]">مدفوع من التحصيل:</strong>
                            <input type="text" readonly
                                class="w-full mt-1 border rounded-lg px-3 py-2 bg-[rgb(var(--primary-50))] text-right font-bold"
                                value="{{ number_format($paidFromCollections, 2) }}">
                        </div>

                        <!-- المجموع النهائي -->
                        <div class="text-sm">
                            <strong class="text-[rgb(var(--primary-700))]">إجمالي المدفوع:</strong>
                            <input type="text" readonly
                                class="w-full mt-1 border rounded-lg px-3 py-2 bg-[rgb(var(--primary-50))] text-right font-bold"
                                value="{{ number_format($paidTotal, 2) }}">
                        </div>

                        <!-- المبلغ المتبقي الكلي -->
                        <div class="text-sm">
                            <strong class="text-[rgb(var(--primary-700))]">المبلغ المتبقي:</strong>
                            <input type="text" readonly
                                class="w-full mt-1 border rounded-lg px-3 py-2 bg-[rgb(var(--primary-50))] text-right font-bold"
                                value="{{ number_format($remainingAmount, 2) }}">
                        </div>
                    </div>

                    <!-- تسديد المتبقي (يأخذ السطر كامل) -->
                    <div class="text-sm mb-4">
                        <strong class="text-[rgb(var(--primary-700))]">تسديد المتبقي:</strong>
                        <input type="number" wire:model.defer="payRemainingNow" max="{{ $isPayToOthersMode ? $availableBalanceToPayOthers : $remainingAmount }}"
                            class="w-full mt-1 border rounded-lg px-3 py-2 bg-[rgb(var(--primary-50))] text-right font-bold focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none"
                            placeholder="أدخل المبلغ أو اتركه كما هو"
                            value="{{ number_format($remainingAmount, 2) }}"
                            @if ($remainingAmount <= 0) disabled @endif>
                    </div>

                    <!-- خدمات المبيعات -->
                    <div class="space-y-3 mb-4">
                        @foreach ($services as $index => $service)
                            <div class="grid grid-cols-3 gap-2 items-center text-sm">
                                <!-- اسم الخدمة -->
                                <input type="text" readonly value="{{ $service['name'] }}"
                                    class="border rounded-lg px-3 py-2 bg-[rgb(var(--primary-50))] text-[rgb(var(--primary-700))]" />

                                <!-- المبلغ المدفوع مسبقاً -->
                                <input type="text" readonly value="{{ number_format($service['paid'], 2) }}"
                                    class="border rounded-lg px-3 py-2 bg-[rgb(var(--primary-50))] text-[rgb(var(--primary-700))] text-right" />

                                <!-- حقل الإدخال -->
                                <input type="number" wire:model.defer="services.{{ $index }}.amount"
                                    class="border rounded-lg px-3 py-2 text-right focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none"
                                    @if ($remainingAmount <= 0) disabled @endif />
                            </div>
                        @endforeach
                    </div>

                    <!-- أزرار التحكم -->
                    <div class="flex justify-between gap-4 mt-4">
                        <button wire:click="cancelEdit"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300">
                            إلغاء
                        </button>
                        
                        @php
                            $disableSaveButton = false;

                            if ($isPayToOthersMode) {
                                $disableSaveButton = !$selectedPayCustomerId; // ✅ نغلق الزر فقط إذا لم يُحدد مستفيد
                            }
                        @endphp


                        <button wire:click="saveAmounts"
                            class="flex-1 text-white font-bold px-4 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300"
                            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);"
                            @if($disableSaveButton) disabled @endif>
                            حفظ التغييرات
                        </button>

                    </div>
                </div>
            </div>
        @endif

      
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
