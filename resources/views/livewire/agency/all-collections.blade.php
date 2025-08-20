<div class="space-y-6">
    <!-- العنوان الرئيسي -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            جميع عمليات البيع
        </h2>
        <div class="flex justify-end mb-4">
            <a href="{{ route('agency.employee-collections.all') }}"
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

    <!-- حقل البحث -->
   <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
    <div class="relative">
        <input type="text" wire:model.live="search"
               placeholder="ابحث باسم المستفيد أو المبلغ أو رقم العملية..."
               class="w-full pr-10 pl-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[rgb(var(--primary-300))] focus:border-[rgb(var(--primary-300))] transition">
        <div class="absolute left-3 top-2.5 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    <input type="date" wire:model.live="startDate"
           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[rgb(var(--primary-300))] focus:border-[rgb(var(--primary-300))] transition"
           placeholder="من تاريخ">

    <div class="flex gap-2">
        <input type="date" wire:model.live="endDate"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[rgb(var(--primary-300))] focus:border-[rgb(var(--primary-300))] transition"
               placeholder="إلى تاريخ">
        <button type="button" wire:click="clearDateFilters"
                class="px-3 py-2 rounded-lg border bg-white text-sm hover:shadow">
            مسح
        </button>
    </div>
</div>

    {{-- عرض كجدول بدلاً من البطاقات --}}
    @if ($sales->isEmpty())
        <div class="bg-white rounded-xl shadow-md p-6 text-center">
            <p class="text-gray-500 text-sm">
                @if ($search)
                    لا توجد نتائج مطابقة للبحث "{{ $search }}"
                @else
                    لا توجد عمليات بيع حتى الآن.
                @endif
            </p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-right divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-3 py-2">المستفيد</th>
                            <th class="px-3 py-2">الحالة</th>
                            <th class="px-3 py-2">الإجمالي $</th>
                            <th class="px-3 py-2">المحصل $</th>
                            <th class="px-3 py-2">عدد التحصيلات</th>
                            <th class="px-3 py-2">أُنشئت</th>
                            <th class="px-3 py-2">إجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($sales as $sale)
                            @php
                                $paymentStatus = $this->getPaymentStatus($sale);
                                $total = $sale->invoice_total_true ?? $sale->usd_sell;
                                $collected = $sale->collections->sum('amount');
                                $count = $sale->collections->count();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-medium text-gray-800">
                                    {{ $sale->beneficiary_name }}
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $paymentStatus['color'] }}">
                                        {{ $paymentStatus['status'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">{{ number_format($total, 2) }}</td>
                                <td class="px-3 py-2">{{ number_format($collected, 2) }}</td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs"
                                          style="background-color: rgba(var(--primary-100), 0.3); color: rgb(var(--primary-700));">
                                        {{ $count }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-500">
                                    {{ $sale->created_at->diffForHumans() }}
                                </td>
                                <td class="px-3 py-2">
                                    <button wire:click="showCollectionDetails({{ $sale->id }})"
                                        class="text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] underline text-sm">
                                        عرض التفاصيل
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (method_exists($sales, 'links'))
                <div class="px-4 py-3 border-t bg-gray-50">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Modal لعرض التفاصيل -->
    @if ($activeSaleId)
        @php $sale = $sales->firstWhere('id', $activeSaleId); @endphp
        @php
            $totalPaid = ($sale->amount_paid ?? 0) + $sale->collections->sum('amount');
            $remainingAmount = $sale->usd_sell - $totalPaid;
        @endphp

        <div class="fixed inset-0 flex items-start justify-center pt-24 z-50 backdrop-blur-sm bg-white/10">
            <div
                class="bg-white/90 rounded-xl shadow-lg w-full max-w-2xl p-6 relative max-h-[80vh] overflow-y-auto border border-gray-200 backdrop-blur-md">
                <button wire:click="closeModal"
                    class="absolute top-2 left-3 text-gray-500 hover:text-red-600 text-lg">&times;</button>

                <h3 class="text-lg font-bold mb-4 text-[rgb(var(--primary-700))] border-b pb-2">
                    تفاصيل التحصيلات لـ {{ $sale->beneficiary_name }}
                </h3>

                <!-- بطاقة ملخص المدفوعات -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50/50 p-4 rounded-lg border border-blue-100">
                        <div class="text-sm text-blue-600 font-medium">إجمالي الفاتورة</div>
                        <div class="text-xl font-bold text-blue-800 mt-1">
                            {{ number_format($sale->invoice_total_true ?? $sale->usd_sell, 2) }} $
                        </div>
                    </div>

                    <div class="bg-rose-50/50 p-4 rounded-lg border border-rose-100">
                        <div class="text-sm text-rose-600 font-medium">إجمالي الاستردادات</div>
                        <div class="text-xl font-bold text-rose-800 mt-1">
                            {{ number_format($sale->refund_total ?? 0, 2) }} $
                        </div>
                    </div>

                    <div class="bg-green-50/50 p-4 rounded-lg border border-green-100">
                        <div class="text-sm text-green-600 font-medium">إجمالي المدفوع</div>
                        <div class="text-xl font-bold text-green-800 mt-1">
                            {{ number_format($totalPaid, 2) }} $
                        </div>
                    </div>

                    <div class="bg-amber-50/50 p-4 rounded-lg border border-amber-100">
                        <div class="text-sm text-amber-600 font-medium">المبلغ المتبقي</div>
                        <div class="text-xl font-bold text-amber-800 mt-1">
                            {{ number_format(max($remainingAmount, 0), 2) }} $
                        </div>
                    </div>
                </div>

                @if ($sale->remaining_for_company > 0)
                    <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded">
                        الشركة مدينة للمستفيد بمبلغ:
                        <strong>${{ number_format($sale->remaining_for_company, 2) }}</strong>
                    </div>
                @endif

                @if ($sale->remaining_for_customer > 0)
                    <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded">
                        على المستفيد دفع المتبقي:
                        <strong>${{ number_format($sale->remaining_for_customer, 2) }}</strong>
                    </div>
                @endif

                <!-- جدول السيناريوهات التفصيلية -->
                @if($sale->scenarios && count($sale->scenarios))
                    <div class="mb-6 overflow-x-auto rounded-xl shadow-sm bg-white">
                        <h4 class="font-medium text-gray-800 mb-2">تفاصيل العمليات للمستفيدة</h4>
                        <table class="min-w-full divide-y divide-gray-200 text-sm text-right">
                            <thead class="bg-gray-100 text-gray-600">
                                <tr>
                                    <th class="px-3 py-2">المرحلة</th>
                                    <th class="px-3 py-2">التاريخ</th>
                                    <th class="px-3 py-2">السعر</th>
                                    <th class="px-3 py-2">المدفوع</th>
                                    <th class="px-3 py-2">الحالة</th>
                                    <th class="px-3 py-2">المرجع</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($sale->scenarios as $index => $s)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2">
                                            @if($index === 0)
                                                الحجز الأول
                                            @else
                                                @switch($s['status'])
                                                    @case('Refund-Full') استرداد كلي @break
                                                    @case('Refund-Partial') استرداد جزئي @break
                                                    @case('Void') إلغاء @break
                                                    @case('Re-Issued') إعادة إصدار @break
                                                    @case('Re-Route') تغيير مسار @break
                                                    @case('Issued') إصدار @break
                                                    @default تعديل
                                                @endswitch
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">{{ $s['date'] }}</td>
                                        <td class="px-3 py-2 text-blue-700">${{ number_format($s['usd_sell'], 2) }}</td>
                                        <td class="px-3 py-2 text-green-700">${{ number_format($s['amount_paid'], 2) }}</td>
                                        <td class="px-3 py-2">{{ $s['status'] }}</td>
                                        <td class="px-3 py-2">{{ $s['note'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="text-sm text-gray-700 mt-2">
                    الإحالة عن طريق العميل:
                    <strong>{{ $sale->referred_by_customer }}</strong>
                </div>

                <!-- جدول سجل التحصيلات -->
                <div class="overflow-x-auto rounded-xl shadow-sm bg-white">
                    <h4 class="font-medium text-gray-800 mb-2">سجل التحصيلات</h4>
                    <table class="min-w-full divide-y divide-gray-200 text-sm text-right">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="px-3 py-2">المبلغ</th>
                                <th class="px-3 py-2">التاريخ</th>
                                <th class="px-3 py-2">ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sale->collections ?? [] as $col)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-green-700">{{ number_format($col->amount, 2) }} $</td>
                                    <td class="px-3 py-2">{{ $col->payment_date }}</td>
                                    <td class="px-3 py-2">{{ $col->note ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    @endif
</div>
