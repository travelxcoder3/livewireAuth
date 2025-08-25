@php
    use App\Services\ThemeService;
    $themeName  = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors     = ThemeService::getCurrentThemeColors($themeName);
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] bg-white text-xs';
@endphp

<div class="space-y-6" x-data>
    {{-- العنوان --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl sm:text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: .5rem;">
            مراجعة الحسابات
        </h2>
    </div>

    {{-- الفلاتر (باستخدام الـ components) --}}
    <div class="bg-white rounded-xl shadow-md p-4 space-y-3">
        <div class="grid md:grid-cols-6 items-end gap-3">
            <x-input-field
                name="date_from"
                type="date"
                label="من تاريخ"
                wireModel="date_from"
                containerClass="relative"
                fieldClass="{{ $fieldClass }}"
            />

            <x-input-field
                name="date_to"
                type="date"
                label="إلى تاريخ"
                wireModel="date_to"
                containerClass="relative"
                fieldClass="{{ $fieldClass }}"
            />

           <x-select-field
    label="تجميع بحسب"
    name="group_by"
    wireModel="group_by"
    :options="[
        'service_type' => 'الخدمة',
        'customer'     => 'العميل',
        'provider'     => 'المزوّد',
        'employee'     => 'الموظف',
        'none'         => 'بدون',
    ]"
    placeholder="اختر"
/>

            <x-select-field
                label="الخدمة"
                name="service_type_id"
                wireModel="service_type_id"
                :options="$serviceTypeOptions"
                :optionsWire="'serviceTypeOptions'"
                :selectedLabelWire="'serviceTypeLabel'"
                placeholder="جميع الخدمات"
                containerClass="relative"
            />

            <x-select-field
                label="العميل"
                name="customer_id"
                wireModel="customer_id"
                :options="$customerOptions"
                :optionsWire="'customerOptions'"
                :selectedLabelWire="'customerLabel'"
                placeholder="جميع العملاء"
                containerClass="relative"
            />

            <x-select-field
                label="المزوّد"
                name="provider_id"
                wireModel="provider_id"
                :options="$providerOptions"
                :optionsWire="'providerOptions'"
                :selectedLabelWire="'providerLabel'"
                placeholder="جميع المزوّدين"
                containerClass="relative"
            />
        </div>

        <div class="flex justify-end gap-2">
            <x-primary-button wire:click="resetFilters">
                إعادة تعيين
            </x-primary-button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="bg-white rounded-xl shadow-md p-4 overflow-x-auto">
        <table class="min-w-full border text-xs sm:text-sm text-center">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 border">المبيعات</th>
                    <th class="px-3 py-2 border">التكلفة</th>
                    <th class="px-3 py-2 border">عمولة الموظف</th>
                    <th class="px-3 py-2 border">عمولة العميل</th>
                    <th class="px-3 py-2 border">صافي الربح</th>
                    <th class="px-3 py-2 border">الاسترجاعات</th>
                    <th class="px-3 py-2 border">التحصيلات</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-3 py-2 border">{{ number_format($kpis['sales'] ?? 0,2) }}</td>
                    <td class="px-3 py-2 border">{{ number_format($kpis['costs'] ?? 0,2) }}</td>
                    <td class="px-3 py-2 border">{{ number_format($kpis['employeeComms'] ?? 0,2) }}</td>
                    <td class="px-3 py-2 border">{{ number_format($kpis['commissions'] ?? 0,2) }}</td>
                    <td class="px-3 py-2 border">{{ number_format($kpis['net_profit'] ?? 0,2) }}</td>
                    <td class="px-3 py-2 border">{{ number_format($kpis['refunds'] ?? 0,2) }}</td>
                    <td class="px-3 py-2 border">{{ number_format($kpis['collections'] ?? 0,2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- الجدول --}}
    <div class="bg-white rounded-xl shadow-md p-0 overflow-x-auto">
        @php
            $isGrouped = $group_by !== 'none';
            $pageCount = 0; $pageSales = 0; $pageCost = 0; $pageComm = 0;
        @endphp

        <table class="min-w-full text-xs sm:text-sm text-center border-separate border-spacing-0">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr>
                    @if($isGrouped)
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">المفتاح</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">العدد</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">المبيعات</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">التكلفة</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">عمولة العميل</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">الربح الصافي</th>
                    @else
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">#</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">التاريخ</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">الخدمة</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">العميل</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">المزوّد</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">المبيعات</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">التكلفة</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">عمولة</th>
                        <th class="border-b border-gray-200 px-3 py-2 bg-gray-100">الربح الصافي</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @forelse($salesGrouped as $row)
                    @php
                        $profit = (float)($row->total ?? 0) - (float)($row->cost ?? 0);
                        $pageCount += (int)($row->row_count ?? ($row->rows ?? 1));
                        $pageSales += (float)($row->total ?? 0);
                        $pageCost  += (float)($row->cost ?? 0);
                        $pageComm  += (float)($row->commission ?? 0);
                    @endphp

                    @if($isGrouped)
                        <tr class="{{ $loop->odd ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                            <td class="px-3 py-2 border-t">
                                {{ $keyLabels[$row->key_id] ?? $row->key_id ?? '—' }}
                            </td>
                            <td class="px-3 py-2 border-t">{{ $row->row_count ?? ($row->rows ?? 1) }}</td>
                            <td class="px-3 py-2 border-t">{{ number_format($row->total ?? 0,2) }}</td>
                            <td class="px-3 py-2 border-t">{{ number_format($row->cost ?? 0,2) }}</td>
                            <td class="px-3 py-2 border-t">{{ number_format($row->commission ?? 0,2) }}</td>
                            <td class="px-3 py-2 border-t font-semibold">{{ number_format($profit,2) }}</td>
                        </tr>
                    @else
                        <tr class="{{ $loop->odd ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                            <td class="px-3 py-2 border-t">#{{ $row->id }}</td>
                            <td class="px-3 py-2 border-t">{{ \Carbon\Carbon::parse($row->sale_date)->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 border-t">{{ $row->service->label  ?? '-' }}</td>
                            <td class="px-3 py-2 border-t">{{ $row->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2 border-t">{{ $row->provider->name ?? '-' }}</td>
                            <td class="px-3 py-2 border-t">{{ number_format($row->total,2) }}</td>
                            <td class="px-3 py-2 border-t">{{ number_format($row->cost,2) }}</td>
                            <td class="px-3 py-2 border-t">{{ number_format($row->commission ?? 0,2) }}</td>
                            <td class="px-3 py-2 border-t font-semibold">{{ number_format($profit,2) }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $isGrouped ? 6 : 9 }}" class="px-4 py-6 text-gray-400">
                            لا توجد بيانات مطابقة للفلاتر
                        </td>
                    </tr>
                @endforelse
            </tbody>

            {{-- تذييل إجماليات الصفحة --}}
            <tfoot class="bg-gray-100">
                @if($isGrouped)
                    <tr>
                        <th class="px-3 py-2 border-t">إجمالي الصفحة</th>
                        <th class="px-3 py-2 border-t">{{ number_format($pageCount,0) }}</th>
                        <th class="px-3 py-2 border-t">{{ number_format($pageSales,2) }}</th>
                        <th class="px-3 py-2 border-t">{{ number_format($pageCost,2) }}</th>
                        <th class="px-3 py-2 border-t">{{ number_format($pageComm,2) }}</th>
                        <th class="px-3 py-2 border-t font-semibold">{{ number_format($pageSales - $pageCost,2) }}</th>
                    </tr>
                @else
                    <tr>
                        <th class="px-3 py-2 border-t text-right" colspan="5">إجمالي الصفحة</th>
                        <th class="px-3 py-2 border-t">{{ number_format($pageSales,2) }}</th>
                        <th class="px-3 py-2 border-t">{{ number_format($pageCost,2) }}</th>
                        <th class="px-3 py-2 border-t">{{ number_format($pageComm,2) }}</th>
                        <th class="px-3 py-2 border-t font-semibold">{{ number_format($pageSales - $pageCost,2) }}</th>
                    </tr>
                @endif
            </tfoot>
        </table>

        <div class="px-4 py-2 border-t border-gray-200">
            {{ $salesGrouped->links() }}
        </div>
    </div>
</div>
