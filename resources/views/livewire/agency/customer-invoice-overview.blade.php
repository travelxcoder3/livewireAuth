@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp

<!-- موبايل فقط ≤640px -->
<style>
@media (max-width:640px){
  .cust-head{flex-direction:column; align-items:flex-start; gap:.5rem}
  .cust-head .btn-wrap{width:100%; display:flex; gap:.5rem; flex-wrap:wrap}
  .cust-head .btn-wrap > *{flex:1 1 auto}

  .filters-grid{grid-template-columns:1fr !important}
  .filters-grid .reset-wrap{margin-top:.25rem !important; justify-content:stretch !important}
  .filters-grid .reset-wrap > button{width:100%}

  .cust-table{font-size:12px}
  /* إخفاء أعمدة ثقيلة على الموبايل: الخدمة(4) + الاستردادات(6) + المحصل(7) */
  .cust-table thead th:nth-child(4),
  .cust-table tbody td:nth-child(4),
  .cust-table thead th:nth-child(6),
  .cust-table tbody td:nth-child(6),
  .cust-table thead th:nth-child(7),
  .cust-table tbody td:nth-child(7){display:none}
}
</style>

<div class="space-y-6">

    <!-- العنوان وزر الرجوع + الطباعة -->
    <div class="flex justify-between items-center mb-4 cust-head">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            فاتورة العميل: {{ $customer->name }}
        </h2>

        @php
            $currency     = Auth::user()->agency->currency ?? 'USD';
            $rows         = collect($collections ?? []);
            $sumBase      = $rows->sum('invoice_total_true');
            $sumRefund    = $rows->sum('refund_total');
            $sumCollected = $rows->sum('total_collected');
            $netTotal     = $sumBase - $sumRefund - $sumCollected;
            $netClass     = $netTotal > 0 ? 'text-rose-700 border-rose-300'
                          : ($netTotal < 0 ? 'text-green-700 border-green-300' : 'text-gray-700 border-gray-300');
        @endphp

        <div class="flex items-center gap-3 btn-wrap">
            <label class="text-xs sm:text-sm font-semibold text-gray-700">الإجمالي:</label>
            <input type="text" value="{{ number_format($netTotal, 2) }} {{ $currency }}" readonly
                   class="bg-white border rounded px-3 py-1 text-sm w-36 text-center font-bold {{ $netClass }}">

            <x-primary-button type="button"
                              wire:click="askBulkTax"
                              wire:loading.attr="disabled"
                              wire:target="toggleSelectAll,applyFilters,askBulkTax"
                              class="flex items-center justify-center gap-2 w-full sm:w-auto">
                إصدار فاتورة مجمّعة
            </x-primary-button>

            <a href="{{ route('agency.customer-detailed-invoices') }}"
               class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg border transition text-sm font-medium
                      bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))] hover:shadow-md hover:text-[rgb(var(--primary-700))] w-full sm:w-auto">
                <svg class="h-5 w-5 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>رجوع</span>
            </a>
        </div>
    </div>

    <!-- شريط الفلاتر -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end filters-grid">
            <x-input-field name="beneficiary_search" label="بحث باسم المستفيد" wireModel="search" placeholder="مثال: أمير علي"/>
            <x-date-picker name="fromDate" label="من تاريخ" wireModel="fromDate" />
            <x-date-picker name="toDate"   label="إلى تاريخ" wireModel="toDate" />
            <div class="reset-wrap mt-1 md:mt-3 flex md:justify-end">
                <button type="button" wire:click="resetFilters"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
                    إعادة تعيين الفلاتر
                </button>
            </div>
        </div>
    </div>

    <!-- جدول المستفيدين -->
    <div x-data="{ groups: @entangle('selectedGroups') }"
         x-init="
            const header = $refs.selectAll;
            const sync = () => {
              const len  = Array.isArray(groups) ? groups.length : (groups?.length ?? 0);
              const rows = {{ count($collections) }};
              header.checked       = (len > 0 && len === rows);
              header.indeterminate = (len > 0 && len < rows);
            };
            sync(); $watch('groups', sync);
         "
         class="rounded-xl shadow bg-white overflow-hidden">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-[11px] sm:text-xs text-right cust-table">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-2 py-1 text-center">
                            <input type="checkbox" x-ref="selectAll" @click.prevent="$wire.toggleSelectAll()"
                                   class="rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]"
                                   style="accent-color: rgb(var(--primary-500));" title="تحديد/إلغاء تحديد الكل">
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
                                <div class="flex items-center gap-2">
                                    <button wire:click="showDetails({{ $index }})"
                                            class="text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold transition">
                                        تفاصيل
                                    </button>
                                    <span class="mx-1 text-gray-300">|</span>
                                    <button type="button"
                                            wire:click="askSingleTax('{{ (string)$item->group_key }}')"
                                            wire:loading.attr="disabled"
                                            class="text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold transition">
                                        فاتورة PDF
                                    </button>
                                </div>
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
    @if($showSingleTaxModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 relative">
        <button wire:click="$set('showSingleTaxModal', false)"
            class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
        <h2 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">إدخال ضريبة الفاتورة</h2>

        <div class="mb-4">
        <label class="block text-sm font-semibold mb-1">
            الضريبة
            <span class="text-gray-500 text-xs">({{ $singleTaxIsPercent ? '%' : ($agencyCurrency ?? 'USD') }})</span>
        </label>
        <div class="flex gap-2">
            <input type="number" step="0.01" min="0" wire:model.defer="singleTaxAmount"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <select wire:model="singleTaxIsPercent"
                    class="rounded-lg border border-gray-300 px-2 py-2 text-sm">
            <option value="1">%</option>
            <option value="0">{{ $agencyCurrency ?? 'USD' }}</option>
            </select>
        </div>
        </div>

        <div class="flex justify-end gap-2 mt-2">
        <button type="button" wire:click="$set('showSingleTaxModal', false)"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">إلغاء</button>
        <x-primary-button wire:click="confirmSingleTax" wire:loading.attr="disabled">تنزيل PDF</x-primary-button>
        </div>
    </div>
    </div>
    @endif

    @if($showBulkTaxModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 relative">
        <button wire:click="$set('showBulkTaxModal', false)"
            class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
        <h2 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">إصدار فاتورة مجمّعة</h2>

        <div class="mb-4">
        <label class="block text-sm font-semibold mb-1">
            الضريبة (للفاتورة المجمّعة)
            <span class="text-gray-500 text-xs">({{ $bulkTaxIsPercent ? '%' : ($agencyCurrency ?? 'USD') }})</span>
        </label>
        <div class="flex gap-2">
            <input type="number" step="0.01" min="0" wire:model.defer="bulkTaxAmount"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <select wire:model="bulkTaxIsPercent"
                    class="rounded-lg border border-gray-300 px-2 py-2 text-sm">
            <option value="1">%</option>
            <option value="0">{{ $agencyCurrency ?? 'USD' }}</option>
            </select>
        </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-3 text-sm mb-4">
        <div class="flex justify-between"><span>Subtotal:</span><span>{{ number_format($bulkSubtotal,2) }} {{ $agencyCurrency ?? 'USD' }}</span></div>
        @php $__tax = $bulkTaxIsPercent ? round($bulkSubtotal * ((float)$bulkTaxAmount/100),2) : (float)$bulkTaxAmount; @endphp
        <div class="flex justify-between"><span>Tax:</span><span>{{ number_format($__tax,2) }} {{ $agencyCurrency ?? 'USD' }}</span></div>
        <div class="flex justify-between font-semibold"><span>Grand:</span><span>{{ number_format($bulkSubtotal + $__tax,2) }} {{ $agencyCurrency ?? 'USD' }}</span></div>
        </div>

        <div class="flex justify-end gap-2 mt-2">
        <button type="button" wire:click="$set('showBulkTaxModal', false)"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">إلغاء</button>
        <x-primary-button wire:click="confirmBulkTax" wire:loading.attr="disabled">تنزيل PDF</x-primary-button>
        </div>
    </div>
    </div>
    @endif


</div>
