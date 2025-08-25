@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] bg-white text-xs';

    use App\Tables\InvoiceTable;
    $columns = InvoiceTable::columns();
@endphp

<div class="space-y-6" x-data>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl sm:text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: .5rem;">
            مراجعة الفواتير
        </h2>
        
    </div>

    <div class="bg-white rounded-xl shadow-md p-4 space-y-3">
            <div class="grid md:grid-cols-5 items-end gap-3">
                <x-input-field
                    name="invoice_number"
                    label="رقم الفاتورة"
                    wireModel="numberSearch"
                    placeholder="INV-..."
                    containerClass="relative"
                    fieldClass="{{ $fieldClass }}"
                />

                <x-input-field
                    name="entity_name"
                    label="اسم الجهة"
                    wireModel="entitySearch"
                    placeholder="العميل / الجهة"
                    containerClass="relative"
                    fieldClass="{{ $fieldClass }}"
                />

                <x-select-field
                    label="اسم المُصدر"
                    name="user"
                    wireModel="userFilter"
                    :options="[]"
                    placeholder="جميع الموظفين"
                    containerClass="relative"
                    :optionsWire="'userOptions'"
                    :selectedLabelWire="'userLabel'"
                    searchKey="userSearch"
                />

                <x-date-picker
                    name="start_date"
                    label="من تاريخ"
                    placeholder="اختر التاريخ"
                    wireModel="startDate"
                    containerClass="relative"
                    width="w-full"
                />

                <x-date-picker
                    name="end_date"
                    label="إلى تاريخ"
                    placeholder="اختر التاريخ"
                    wireModel="endDate"
                    containerClass="relative"
                    width="w-full"
                />
            </div>


            <div class="flex justify-end gap-2">
                <button wire:click="resetFilters"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
                    إعادة تعيين
                </button>
    </div>


    <div class="overflow-x-auto">
        <x-data-table :rows="$invoices" :columns="$columns" />
    </div>

    @if ($invoices->hasPages())
        <div class="px-4 py-2 border-t border-gray-200">
            {{ $invoices->links() }}
        </div>
    @endif
</div>

    @if($showDetailsModal && $selectedInvoice)
        @php
            $agencyCurrency = $selectedInvoice->agency->currency ?? (Auth::user()?->agency?->currency ?? 'USD');
            $subtotal = (float)($selectedInvoice->subtotal
                ?? $selectedInvoice->sales->sum(fn($s)=> (float)($s->pivot->base_amount ?? $s->usd_sell)));
            $tax   = (float)($selectedInvoice->tax_total ?? 0);
            $grand = (float)($selectedInvoice->grand_total ?? ($subtotal + $tax));
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-3xl rounded-xl shadow-xl p-6 relative">
                <button wire:click="$set('showDetailsModal', false)"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>

                <h3 class="text-xl font-bold text-center mb-4" style="color: rgb(var(--primary-700));">
                    تفاصيل الفاتورة: {{ $selectedInvoice->invoice_number }}
                </h3>

                <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                    <div><strong>التاريخ:</strong> {{ $selectedInvoice->date }}</div>
                    <div><strong>الجهة:</strong> {{ $selectedInvoice->entity_name }}</div>
                    <div><strong>المُصدر:</strong> {{ $selectedInvoice->user->name ?? '-' }}</div>
                    <div><strong>عدد العمليات:</strong> {{ $selectedInvoice->sales->count() }}</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border-b">#</th>
                                <th class="p-2 border-b">الخدمة</th>
                                <th class="p-2 border-b">PNR</th>
                                <th class="p-2 border-b">المرجع</th>
                                <th class="p-2 border-b">الصافي</th>
                                <th class="p-2 border-b">الضريبة</th>
                                <th class="p-2 border-b">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedInvoice->sales as $s)
                                @php
                                    $base = (float)($s->pivot->base_amount ?? $s->usd_sell);
                                    $taxL = (float)($s->pivot->tax_amount ?? 0);
                                    $totL = $base + $taxL;
                                @endphp
                                <tr>
                                    <td class="p-2 border-b">{{ $loop->iteration }}</td>
                                    <td class="p-2 border-b">{{ $s->service->label ?? '-' }}</td>
                                    <td class="p-2 border-b">{{ $s->pnr ?? '-' }}</td>
                                    <td class="p-2 border-b">{{ $s->reference ?? '-' }}</td>
                                    <td class="p-2 border-b">{{ number_format($base,2) }} {{ $agencyCurrency }}</td>
                                    <td class="p-2 border-b">{{ number_format($taxL,2) }} {{ $agencyCurrency }}</td>
                                    <td class="p-2 border-b font-semibold">{{ number_format($totL,2) }} {{ $agencyCurrency }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-sm">
                    <div><strong>Subtotal:</strong> {{ number_format($subtotal,2) }} {{ $agencyCurrency }}</div>
                    <div><strong>Tax:</strong> {{ number_format($tax,2) }} {{ $agencyCurrency }}</div>
                    <div class="font-bold"><strong>Grand:</strong> {{ number_format($grand,2) }} {{ $agencyCurrency }}</div>
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    @can('accounts.print')
                        <x-primary-button wire:click="downloadPdf({{ $selectedInvoice->id }})">تحميل PDF</x-primary-button>
                    @endcan
                </div>
            </div>
        </div>
    @endif

</div>
