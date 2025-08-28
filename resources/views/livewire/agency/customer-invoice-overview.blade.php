@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp

<div class="space-y-6">

    <!-- العنوان وزر الرجوع + الطباعة -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            كشف حساب مفصل للعميل: {{ $customer->name }}
        </h2>

        <div class="flex items-center gap-2">
            @if (count($selectedGroups) > 0)
                <x-primary-button
                    type="button"
                    wire:click="exportSelected"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-60 cursor-not-allowed"
                    wire:target="toggleSelectAll,applyFilters,exportSelected"
                    class="flex items-center gap-2"
                >
                    طباعة فواتير محددة
                </x-primary-button>
            @endif


            <a href="{{ route('agency.customer-detailed-invoices') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg border transition text-sm font-medium
                      bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))] hover:shadow-md hover:text-[rgb(var(--primary-700))]">
                <svg class="h-5 w-5 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>رجوع</span>
            </a>
        </div>
    </div>

<!-- شريط الفلاتر -->
<div class="bg-white rounded-xl shadow-md p-4">
    <div class="grid md:grid-cols-4 gap-4 items-end">
        {{-- بحث باسم المستفيد --}}
        <x-input-field
            name="beneficiary_search"
            label="بحث باسم المستفيد"
            wireModel="search"
            placeholder="مثال: أمير علي"
            containerClass="relative mt-1"
            {{-- لو عندك متغيرات ستايل جاهزة استخدمها، وإلا اترك المكوّن يطبق الافتراضي --}}
            />

        {{-- من تاريخ --}}
        <x-date-picker
            name="fromDate"
            label="من تاريخ"
            placeholder="اختر التاريخ"
            wireModel="fromDate"
            />

        {{-- إلى تاريخ --}}
        <x-date-picker
            name="toDate"
            label="إلى تاريخ"
            placeholder="اختر التاريخ"
            wireModel="toDate"
            />

        {{-- زر مسح الفلاتر --}}
        <div class="mt-3 flex justify-between">
            <div></div>
            <button type="button"
                    wire:click="resetFilters"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                إعادة تعيين الفلاتر
            </button>
        </div>

    </div>
</div>


    <!-- جدول المستفيدين -->
    <div
        x-data="{ groups: @entangle('selectedGroups') }"
        x-init="
            const total  = {{ count($collections) }};
            const header = $refs.selectAll;
            const sync = () => {
                const len = Array.isArray(groups) ? groups.length : (groups?.length ?? 0);
                const rows = {{ count($collections) }};
                header.checked       = (len > 0 && len === rows);
                header.indeterminate = (len > 0 && len < rows);
            };
            sync();
            $watch('groups', sync);
        "
        class="overflow-x-auto rounded-xl shadow bg-white"
    >
       <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-2 py-1 text-center">
                        <input type="checkbox"
                            x-ref="selectAll"
                            @click.prevent="$wire.toggleSelectAll()"
                            class="rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]"
                            style="accent-color: rgb(var(--primary-500));"
                            title="تحديد/إلغاء تحديد الكل">
                    </th>
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

            <tbody class="bg-white divide-y divide-gray-100">
                @php $currency = Auth::user()->agency->currency ?? 'USD'; @endphp

                @forelse($collections as $index => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 py-1 text-center">
                            <input type="checkbox"
                                   wire:key="cb-{{ (string)$item->group_key }}"
                                   wire:model.live="selectedGroups"
                                   value="{{ (string)$item->group_key }}"
                                   class="rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]"
                                   style="accent-color: rgb(var(--primary-500));">
                        </td>

                        <td class="px-2 py-1">{{ $item->beneficiary_name }}</td>
                        <td class="px-2 py-1">{{ $item->sale_date }}</td>
                        <td class="px-2 py-1">{{ $item->service_label }}</td>

                        <td class="px-2 py-1 text-blue-700">
                            {{ number_format($item->invoice_total_true, 2) }} {{ $currency }}
                        </td>
                        <td class="px-2 py-1 text-rose-700">
                            {{ number_format($item->refund_total, 2) }} {{ $currency }}
                        </td>
                        <td class="px-2 py-1 text-green-700">
                            {{ number_format($item->total_collected, 2) }} {{ $currency }}
                        </td>

                        <td class="px-2 py-1">
                            @if ($item->remaining_for_customer > 0)
                                <span class="text-red-600">
                                    مدين: {{ number_format($item->remaining_for_customer, 2) }} {{ $currency }}
                                </span>
                            @elseif ($item->remaining_for_company > 0)
                                <span class="text-green-600">
                                    للعميل: {{ number_format($item->remaining_for_company, 2) }} {{ $currency }}
                                </span>
                            @else
                                <span class="text-gray-500">تم السداد بالكامل</span>
                            @endif
                        </td>

                        <td class="px-2 py-1">
                            <button
                                wire:click="showDetails({{ $index }})"
                                class="text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold transition">
                                تفاصيل
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-gray-400 py-6">لا توجد نتائج مطابقة للبحث.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    </div>

    <!-- ✅ المودال -->
    @if ($activeSale)
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-24 backdrop-blur-sm bg-black/30">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6 relative max-h-[80vh] overflow-y-auto">
                <button wire:click="closeModal"
                        class="absolute top-2 left-3 text-gray-500 hover:text-red-600 text-lg">&times;</button>

                <h3 class="text-lg font-bold mb-4 text-[rgb(var(--primary-700))] border-b pb-2">
                    تفاصيل التحصيلات لـ {{ $activeSale->beneficiary_name }}
                </h3>

                <!-- جدول العمليات -->
                <div class="mb-6 overflow-x-auto">
                    <h4 class="font-medium text-gray-800 mb-2">تفاصيل العمليات للمستفيد</h4>
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="px-3 py-2">المرحلة</th>
                                <th class="px-3 py-2">التاريخ</th>
                                <th class="px-3 py-2">سعر الخدمة</th>
                                <th class="px-3 py-2">المدفوع</th>
                                <th class="px-3 py-2">الحالة</th>
                                <th class="px-3 py-2">المرجع</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($activeSale->scenarios ?? [] as $i => $s)
                                <tr>
                                    <td class="px-3 py-2">{{ $i === 0 ? 'الحجز الأول' : ($s['status'] ?? 'تعديل') }}</td>
                                    <td class="px-3 py-2">{{ $s['date'] ?? '-' }}</td>
                                    @php $currency = Auth::user()->agency->currency ?? 'USD'; @endphp
                                    <td class="px-3 py-2 text-blue-700">{{ number_format($s['usd_sell'], 2) }} {{ $currency }}</td>
                                    <td class="px-3 py-2 text-green-700">{{ number_format($s['amount_paid'], 2) }} {{ $currency }}</td>
                                    <td class="px-3 py-2">{{ $s['status'] ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $s['note'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- جدول سجل التحصيلات -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-2">سجل التحصيلات</h4>
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="px-3 py-2">المبلغ</th>
                                <th class="px-3 py-2">التاريخ</th>
                                <th class="px-3 py-2">ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($activeSale->collections ?? [] as $col)
                                <tr>
                                    <td class="px-3 py-2 text-green-700">{{ number_format($col['amount'], 2) }} {{ $currency }}</td>
                                    <td class="px-3 py-2">{{ $col['payment_date'] }}</td>
                                    <td class="px-3 py-2">{{ $col['note'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>
