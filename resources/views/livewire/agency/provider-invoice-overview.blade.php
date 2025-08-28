@php
    $currency = Auth::user()->agency->currency ?? 'USD';
@endphp

<div class="space-y-6">
   <div class="flex justify-between items-center mb-4"
     x-data="{ sel: @entangle('selectedGroups') }">

    <h2 class="text-2xl font-bold"
        style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: .5rem;">
        كشف حساب مفصل للمزوّد: {{ $provider->name }}
    </h2>

    <div class="flex items-center gap-2">
        <!-- يظهر فقط عند وجود تحديد -->
        <template x-if="Array.isArray(sel) && sel.length > 0">
            <x-primary-button type="button" wire:click="exportSelected"
                              wire:loading.attr="disabled"
                              wire:loading.class="opacity-60 cursor-not-allowed"
                              wire:target="toggleSelectAll,applyFilters,exportSelected"
                              class="flex items-center gap-2">
                تصدير PDF
            </x-primary-button>
        </template>

        <a href="{{ route('agency.provider-detailed-invoices') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-lg border transition text-sm font-medium
                  bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))] hover:shadow-md hover:text-[rgb(var(--primary-700))]">
            <svg class="h-5 w-5 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <span>رجوع</span>
        </a>
    </div>
</div>


    <!-- فلاتر -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4 items-end">
            <x-input-field
                name="beneficiary_search"
                label="بحث باسم المستفيد"
                wireModel="search"
                placeholder="مثال: أمير علي"
            />
            <x-date-picker name="fromDate" label="من تاريخ" wireModel="fromDate" />
            <x-date-picker name="toDate"   label="إلى تاريخ" wireModel="toDate" />
            <div class="mt-3 flex justify-end">
                <button type="button"
                        wire:click="resetFilters"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
                    إعادة تعيين الفلاتر
                </button>
            </div>
        </div>
    </div>

    <!-- جدول المجموعات -->
 <div
    x-data="{ groupsSel: @entangle('selectedGroups') }"
    x-init="
        const h = $refs.selectAll;
        const sync = () => {
            const len  = Array.isArray(groupsSel) ? groupsSel.length : (groupsSel?.length ?? 0);
            const rows = {{ count($groups ?? []) }};
            h.checked       = (len > 0 && len === rows);
            h.indeterminate = (len > 0 && len < rows);
        };
        sync();
        $watch('groupsSel', sync);
    "
    class="bg-white rounded-xl shadow-md overflow-hidden"
>
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
                    <th class="px-2 py-1">تكلفة الخدمة (أصل)</th>
                    <th class="px-2 py-1">الاستردادات</th>
                    <th class="px-2 py-1">صافي مستحق للمزوّد</th>
                    <th class="px-2 py-1">الإجراء</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($groups as $index => $g)
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 py-1 text-center">
                            <input type="checkbox"
                                   wire:key="cb-{{ (string)$g->group_key }}"
                                   wire:model.live="selectedGroups"
                                   value="{{ (string)$g->group_key }}"
                                   class="rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]"
                                   style="accent-color: rgb(var(--primary-500));">
                        </td>

                        <td class="px-2 py-1">{{ $g->beneficiary_name }}</td>
                        <td class="px-2 py-1">{{ $g->sale_date }}</td>
                        <td class="px-2 py-1">{{ $g->service_label }}</td>

                        <td class="px-2 py-1 text-blue-700">
                            {{ number_format($g->cost_total_true, 2) }} {{ $currency }}
                        </td>
                        <td class="px-2 py-1 text-rose-700">
                            {{ number_format($g->refund_total, 2) }} {{ $currency }}
                        </td>
                        <td class="px-2 py-1 text-emerald-700 font-semibold">
                            {{ number_format($g->net_cost, 2) }} {{ $currency }}
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
                        <td colspan="8" class="text-center text-gray-400 py-6">لا توجد نتائج مطابقة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


    <!-- مودال التفاصيل -->
    @if ($activeRow)
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-24 backdrop-blur-sm bg-black/30">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6 relative max-h-[80vh] overflow-y-auto">
                <button wire:click="closeModal"
                        class="absolute top-2 left-3 text-gray-500 hover:text-red-600 text-lg">&times;</button>

                <h3 class="text-lg font-bold mb-4 text-[rgb(var(--primary-700))] border-b pb-2">
                    تفاصيل التكلفة للمستفيد: {{ $activeRow->beneficiary_name }}
                </h3>

                <div class="mb-6 overflow-x-auto">
                    <h4 class="font-medium text-gray-800 mb-2">تفاصيل السيناريوهات</h4>
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="px-3 py-2">المرحلة</th>
                                <th class="px-3 py-2">التاريخ</th>
                                <th class="px-3 py-2">تكلفة الخدمة</th>
                                <th class="px-3 py-2">الحالة</th>
                                <th class="px-3 py-2">المرجع</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($activeRow->scenarios ?? [] as $i => $s)
                                <tr>
                                    <td class="px-3 py-2">{{ $i === 0 ? 'الحجز الأول' : ($s['status'] ?? 'تعديل') }}</td>
                                    <td class="px-3 py-2">{{ $s['date'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-blue-700">{{ number_format($s['usd_buy'] ?? 0, 2) }} {{ $currency }}</td>
                                    <td class="px-3 py-2">{{ $s['status'] ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $s['note'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><strong>أصل التكلفة:</strong> {{ number_format($activeRow->cost_total_true, 2) }} {{ $currency }}</div>
                    <div><strong>الاستردادات:</strong> {{ number_format($activeRow->refund_total, 2) }} {{ $currency }}</div>
                    <div class="col-span-2 font-semibold"><strong>صافي مستحق للمزوّد:</strong> {{ number_format($activeRow->net_cost, 2) }} {{ $currency }}</div>
                </div>
            </div>
        </div>
    @endif
</div>
