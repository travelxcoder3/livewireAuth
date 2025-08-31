@php
    use App\Services\ThemeService;
    $themeName  = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors     = ThemeService::getCurrentThemeColors($themeName);
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] bg-white text-xs';
@endphp

<div class="space-y-6" x-data>
        {{-- العنوان --}}
        <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3">
            <h2 class="text-xl sm:text-2xl font-bold"
                style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: .5rem;">
                مراجعة الحسابات
            </h2>

            <div class="flex items-center gap-2 order-last sm:order-none w-full sm:w-auto">
                <label class="text-xs sm:text-sm font-semibold text-gray-700">الإجمالي:</label>
                <input type="text"
                    value="{{ number_format($kpis['sales'] ?? 0, 2) }}"
                    readonly
                    class="bg-gray-100 border border-gray-300 rounded px-2 sm:px-3 py-1 text-xs sm:text-sm text-gray-700 w-24 sm:w-32 text-center">
            </div>
        </div>


        {{-- الفلاتر (باستخدام الـ components) --}}
        <div class="bg-white rounded-xl shadow-md p-4 space-y-3">
            <div class="grid md:grid-cols-6 items-end gap-3">
                <x-date-picker
                    name="date_from"
                    label="من تاريخ"
                    placeholder="اختر التاريخ"
                    wireModel="date_from"
                    width="w-full"
                    containerClass="relative"
                    fieldClass="{{ $fieldClass }} peer"
                />


                <x-date-picker
                    name="date_to"
                    label="إلى تاريخ"
                    placeholder="اختر التاريخ"
                    wireModel="date_to"
                    width="w-full"
                    containerClass="relative"
                    fieldClass="{{ $fieldClass }} peer"
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
            <button type="button" wire:click="resetFilters"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-xl shadow text-sm">
                إعادة تعيين الفلاتر
                </button>

            </div>
        </div>

        {{-- KPIs strip — single container with vertical dividers --}}
        <div class="bg-white rounded-xl shadow-md p-3">
        <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 divide-x divide-[rgb(var(--primary-200))]">

            <div class="px-3 py-2">
            <dt class="text-[11px] text-gray-500">المبيعات</dt>
            <dd class="mt-1 text-base sm:text-lg font-bold text-[rgb(var(--primary-700))]">
                {{ number_format($kpis['sales'] ?? 0,2) }}
            </dd>
            </div>

            <div class="px-3 py-2">
            <dt class="text-[11px] text-gray-500">التكلفة</dt>
            <dd class="mt-1 text-base sm:text-lg font-bold text-[rgb(var(--primary-700))]">
                {{ number_format($kpis['costs'] ?? 0,2) }}
            </dd>
            </div>

            <div class="px-3 py-2">
            <dt class="text-[11px] text-gray-500">عمولة الموظف</dt>
            <dd class="mt-1 text-base sm:text-lg font-bold text-[rgb(var(--primary-700))]">
                {{ number_format($kpis['employeeComms'] ?? 0,2) }}
            </dd>
            </div>

            <div class="px-3 py-2">
            <dt class="text-[11px] text-gray-500">عمولة العميل</dt>
            <dd class="mt-1 text-base sm:text-lg font-bold text-[rgb(var(--primary-700))]">
                {{ number_format($kpis['commissions'] ?? 0,2) }}
            </dd>
            </div>

            <div class="px-3 py-2">
            <dt class="text-[11px] text-gray-500">صافي الربح</dt>
            <dd class="mt-1 text-base sm:text-lg font-extrabold text-[rgb(var(--primary-700))]">
                {{ number_format($kpis['net_profit'] ?? 0,2) }}
            </dd>
            </div>

            <div class="px-3 py-2">
            <dt class="text-[11px] text-gray-500">الاستردادات</dt>
            <dd class="mt-1 text-base sm:text-lg font-bold text-[rgb(var(--primary-700))]">
                {{ number_format($kpis['refunds'] ?? 0,2) }}
            </dd>
            </div>

            <div class="px-3 py-2">
            <dt class="text-[11px] text-gray-500">التحصيلات</dt>
            <dd class="mt-1 text-base sm:text-lg font-bold text-[rgb(var(--primary-700))]">
                {{ number_format($kpis['collections'] ?? 0,2) }}
            </dd>
            </div>

        </dl>
        </div>


        @php
            use App\Tables\AuditAccountsTable as T;
            $isGrouped = $group_by !== 'none';
            $columns   = $isGrouped ? T::groupedColumns() : T::detailColumns();

            $items     = collect($salesGrouped->items());
            $pageCount = $isGrouped ? $items->sum(fn($r)=> (int)($r->row_count ?? ($r->rows ?? 1))) : 0;
            $pageSales = $items->sum(fn($r)=> (float)($r->total ?? 0));
            $pageCost  = $items->sum(fn($r)=> (float)($r->cost ?? 0));
            $pageComm  = $items->sum(fn($r)=> (float)($r->commission ?? 0));
            $pageNet   = $pageSales - $pageCost;
        @endphp

        <div class="bg-white rounded-xl shadow-md p-4">
            <x-data-table
                    :columns="$columns"
                    :rows="$salesGrouped"
                    wire:key="audit-{{ $group_by }}-{{ md5(json_encode([$date_from,$date_to,$service_type_id,$customer_id,$provider_id,$employee_id])) }}-p{{ $salesGrouped->currentPage() }}"
                >
                    <x-slot name="footer">
                    @if($isGrouped)
                        <tr>
                       <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))]">إجمالي الصفحة</th>
                        <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))]">{{ number_format($pageCount,0) }}</th>
                        <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))]">{{ number_format($pageSales,2) }}</th>
                        <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))]">{{ number_format($pageCost,2) }}</th>
                        <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))]">{{ number_format($pageComm,2) }}</th>
                        <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))] font-semibold">{{ number_format($pageNet,2) }}</th>

                        </tr>
                    @else
                        <tr>
                       <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))] text-right" colspan="5">إجمالي الصفحة</th>
                        <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))]">{{ number_format($pageSales,2) }}</th>
                        <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))]">{{ number_format($pageCost,2) }}</th>
                        <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))]">{{ number_format($pageComm,2) }}</th>
                        <th class="px-3 py-2 border-t-2 border-[rgb(var(--primary-100))] font-semibold">{{ number_format($pageNet,2) }}</th>

                        </tr>
                    @endif
                    </x-slot>
            </x-data-table>
        </div>

</div>
