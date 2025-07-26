<div class="space-y-6">
    <!-- العنوان الرئيسي -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            جميع عمليات البيع
        </h2>
    </div>

    <!-- حقل البحث -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="relative">
            <input 
                type="text" 
                wire:model.live="search"
                placeholder="ابحث باسم المستفيد أو المبلغ أو رقم العملية..." 
                class="w-full pr-10 pl-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[rgb(var(--primary-300))] focus:border-[rgb(var(--primary-300))] transition"
            >
            <div class="absolute left-3 top-2.5 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- عرض البطاقات -->
    @if($sales->isEmpty())
        <div class="bg-white rounded-xl shadow-md p-6 text-center">
            <p class="text-gray-500 text-sm">
                @if($search)
                    لا توجد نتائج مطابقة للبحث "{{ $search }}"
                @else
                    لا توجد عمليات بيع حتى الآن.
                @endif
            </p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($sales as $sale)
                @php $paymentStatus = $this->getPaymentStatus($sale); @endphp
                
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 border border-gray-100">
                    <!-- رأس البطاقة مع حالة التحصيل -->
                    <div class="p-5 bg-gradient-to-r from-[rgba(var(--primary-50))] to-white">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-lg font-semibold text-gray-800 truncate max-w-[70%]" title="{{ $sale->beneficiary_name }}">
                                {{ $sale->beneficiary_name }}
                            </h3>
                            <span class="text-xs px-2 py-1 rounded-full {{ $paymentStatus['color'] }}">
                                {{ $paymentStatus['status'] }}
                            </span>
                        </div>
                    </div>

                    <!-- محتوى البطاقة -->
                    <div class="p-5">
                        <div class="text-sm text-gray-700 space-y-2">
                            <div class="flex justify-between">
                                <span class="font-medium">المبلغ الإجمالي:</span>
                                <span>{{ number_format($sale->usd_sell, 2) }} $</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">المبلغ المحصل:</span>
                                <span>{{ number_format($sale->collections->sum('amount'), 2) }} $</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">عدد التحصيلات:</span>
                                <span class="px-2 py-1 rounded-full text-xs" 
                                      style="background-color: rgba(var(--primary-100), 0.3); color: rgb(var(--primary-700));">
                                    {{ $sale->collections->count() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- تذييل البطاقة -->
                    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-xs text-gray-500">
                            {{ $sale->created_at->diffForHumans() }}
                        </span>
                        <button wire:click="showCollectionDetails({{ $sale->id }})"
                            class="text-sm text-[rgb(var(--primary-600))] underline hover:text-[rgb(var(--primary-700))]">
                            عرض التفاصيل
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Modal لعرض التفاصيل -->
    @if($activeSaleId)
        @php $sale = $sales->firstWhere('id', $activeSaleId); @endphp
        @php
            $totalPaid = $sale->collections->sum('amount');
            $remainingAmount = $sale->usd_sell - $totalPaid;
        @endphp
        
        <div class="fixed inset-0 flex items-center justify-center z-50 backdrop-blur-sm bg-white/10">
            <div class="bg-white/90 rounded-xl shadow-lg w-full max-w-2xl p-6 relative max-h-[80vh] overflow-y-auto border border-gray-200 backdrop-blur-md">
                <button wire:click="closeModal" class="absolute top-2 left-3 text-gray-500 hover:text-red-600 text-lg">&times;</button>

                <h3 class="text-lg font-bold mb-4 text-[rgb(var(--primary-700))] border-b pb-2">
                    تفاصيل التحصيلات لـ {{ $sale->beneficiary_name }}
                </h3>

                <!-- بطاقة ملخص المدفوعات -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50/50 p-4 rounded-lg border border-blue-100">
                        <div class="text-sm text-blue-600 font-medium">إجمالي الفاتورة</div>
                        <div class="text-xl font-bold text-blue-800 mt-1">
                            {{ number_format($sale->usd_sell, 2) }} $
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
                            {{ number_format($remainingAmount, 2) }} $
                        </div>
                    </div>
                </div>

                <!-- جدول التفاصيل -->
                <div class="mb-2 flex justify-between items-center">
                    <h4 class="font-medium text-gray-700">سجل التحصيلات</h4>
                    <span class="text-xs text-gray-500">عدد التحصيلات: {{ $sale->collections->count() }}</span>
                </div>
                
                <table class="w-full text-sm border">
                    <thead>
                        <tr class="bg-gray-100/80">
                            <th class="p-2 border text-right">المبلغ</th>
                            <th class="p-2 border text-right">التاريخ</th>
                            <th class="p-2 border text-right">طريقة الدفع</th>
                            <th class="p-2 border text-right">ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->collections ?? [] as $col)
                            <tr class="hover:bg-gray-50">
                                <td class="p-2 border text-right">{{ number_format($col->amount, 2) }} $</td>
                                <td class="p-2 border text-right">{{ $col->payment_date }}</td>
                                <td class="p-2 border text-right">{{ $col->method }}</td>
                                <td class="p-2 border text-right">{{ $col->note ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>