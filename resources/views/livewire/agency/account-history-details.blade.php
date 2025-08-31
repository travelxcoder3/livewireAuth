@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp

<div class="space-y-6">
    <!-- العنوان الرئيسي + زر الرجوع -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            سجل حساب العميل: {{ $customer->name }}
        </h2>
        <div class="flex justify-end mb-4">
            <a href="{{ route('agency.customer-accounts') }}"
                class="flex items-center gap-2 px-4 py-2 rounded-lg border transition duration-200 text-sm font-medium
               bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))] hover:shadow-md hover:text-[rgb(var(--primary-700))]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform rotate-180" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>رجوع</span>
            </a>
        </div>
    </div>

    <!-- جدول العمليات -->
    <div class="overflow-x-auto rounded-xl shadow-lg bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-2 py-1">اسم المستفيد</th>
                    <th class="px-2 py-1">تاريخ البيع</th>
                    <th class="px-2 py-1">الخدمة</th>
                    <th class="px-2 py-1">سعر الخدمة (أصل)</th>
                    <th class="px-2 py-1">الاستردادات</th>
                    <th class="px-2 py-1">المحصل (إجمالي)</th>
                    <th class="px-2 py-1">المتبقي / رصيد للعميل</th>
                    <th class="px-2 py-1">الإجراء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($collections as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 py-1">{{ $item->beneficiary_name }}</td>
                        <td class="px-2 py-1">{{ $item->sale_date }}</td>
                        <td class="px-2 py-1">{{ $item->service_label }}</td>
                        <td class="px-3 py-2 text-blue-700">{{ number_format($item->invoice_total_true, 2) }}</td>
                        <td class="px-3 py-2 text-rose-700">{{ number_format($item->refund_total, 2) }}</td>
                        <td class="px-3 py-2 text-green-700">
                            {{ number_format($item->total_collected, 2) }}
                        </td>

                        <td class="px-2 py-1">
                            @if ($item->remaining_for_customer > 0)
                                <span class="text-red-600">مدين: {{ number_format($item->remaining_for_customer, 2) }}</span>
                            @elseif ($item->remaining_for_company > 0)
                                <span class="text-green-600">للعميل: {{ number_format($item->remaining_for_company, 2) }}</span>
                            @else
                                <span class="text-gray-500">تم السداد بالكامل</span>
                            @endif
                        </td>


                        <td class="px-2 py-1">
                            <button wire:click="showDetails({{ $loop->index }})"
                                class="transition duration-200 font-semibold" style="color: rgb(var(--primary-600));"
                                onmouseover="this.style.color='rgb(var(--primary-700))'"
                                onmouseout="this.style.color='rgb(var(--primary-600))'">
                                <i class="fas fa-eye"></i>
                                تفاصيل
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-400">
                            لا توجد عمليات.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($activeSale)
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-24 backdrop-blur-sm bg-black/30">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6 relative max-h-[80vh] overflow-y-auto">
                <!-- زر إغلاق -->
                <button wire:click="closeModal"
                    class="absolute top-2 left-3 text-gray-500 hover:text-red-600 text-lg">&times;</button>

                <h3 class="text-lg font-bold mb-4 text-[rgb(var(--primary-700))] border-b pb-2"
                    style="border-bottom: 2px solid rgba(var(--primary-200), 0.5);">
                    تفاصيل التحصيلات لـ {{ $activeSale->beneficiary_name }}
                </h3>

                <!-- جدول تفاصيل العمليات -->
                <div class="mb-6 overflow-x-auto rounded-xl shadow-sm bg-white">
                    <h4 class="font-medium text-gray-800 mb-2">تفاصيل العمليات للمستفيد</h4>
                    <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="px-2 py-1">المرحلة</th>
                                <th class="px-2 py-1">التاريخ</th>
                                <th class="px-2 py-1">سعر الخدمة</th>
                                <th class="px-2 py-1">المدفوع</th>
                                <th class="px-2 py-1">الحالة</th>
                                <th class="px-2 py-1">المرجع</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($activeSale->scenarios ?? [] as $index => $s)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-1">
                                        @if ($index === 0)
                                            الحجز الأول
                                        @else
                                            {{ $s['status'] ?? 'تعديل' }}
                                        @endif
                                    </td>
                                    <td class="px-2 py-1">{{ $s['date'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-blue-700">${{ number_format($s['usd_sell'], 2) }}</td>
                                    <td class="px-3 py-2 text-green-700">${{ number_format($s['amount_paid'], 2) }}
                                    </td>
                                    <td class="px-2 py-1">{{ $s['status'] ?? '-' }}</td>
                                  @php
                                    $raw = trim($s['note'] ?? '');
                                    $clean = preg_replace('/#?[0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}#?/u','',$raw);
                                    $clean = preg_replace('/\(\s*سجل\s*#?\s*\d+\s*\)/u','',$clean);
                                    $clean = trim(preg_replace('/\s{2,}/u',' ',$clean));
                                    @endphp
                                    <td class="px-2 py-1">{{ $clean !== '' ? $clean : '-' }}</td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- جدول سجل التحصيلات -->
                @php
                    $cols = [
                        ['key' => 'amount',       'label' => 'المبلغ',  'format' => 'money', 'color' => 'green-700'],
                        ['key' => 'payment_date', 'label' => 'التاريخ', 'format' => 'date'],
                        ['key' => 'note',         'label' => 'ملاحظات', 'format' => function($v){
                            $raw = trim((string)$v);
                            $clean = preg_replace('/#?[0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}#?/u','',$raw);
                            $clean = preg_replace('/\(\s*سجل\s*#?\s*\d+\s*\)/u','',$clean);
                            $clean = trim(preg_replace('/\s{2,}/u',' ',$clean));
                            return $clean !== '' ? e($clean) : '-';
                        }],
                    ];
                @endphp

                <x-data-table :rows="$activeSale->collections" :columns="$cols" />



            </div>
        </div>
    @endif
    
</div>
