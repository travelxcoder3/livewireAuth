@php
    use App\Services\ThemeService;
    use App\Tables\AccountTable;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);

    $columns = AccountTable::columns();
    $fieldClass =
        'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
    $labelClass =
        'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
    $containerClass = 'relative mt-1';
    $agencyCurrency = Auth::user()?->agency?->currency ?? 'USD';
@endphp

<div class="space-y-6" x-data="reportBox()">
    <style>[x-cloak]{display:none!important}</style>

    <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3">
        <h2 class="text-xl sm:text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            مراجعة المبيعات
        </h2>

        <div class="flex items-center gap-2 order-last sm:order-none w-full sm:w-auto">
            <label class="text-xs sm:text-sm font-semibold text-gray-700">الإجمالي:</label>
            <input type="text" value="{{ number_format($totalSales, 2) }}" readonly
                   class="bg-gray-100 border border-gray-300 rounded px-2 sm:px-3 py-1 text-xs sm:text-sm text-gray-700 w-24 sm:w-32 text-center">
            @can('accounts.invoice')
                @if ($sales->count())
                    <x-primary-button wire:click="openBulkInvoiceModal"
                        class="ml-0 sm:ml-2 px-3 py-1 text-xs sm:px-4 sm:py-2 sm:text-sm rounded-lg">
                        إصدار فاتورة مجمعة
                    </x-primary-button>
                @endif
            @endcan
        </div>
    </div>

    {{-- فلاتر --}}
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4">
            <x-input-field
                name="employee"
                label="اسم الموظف"
                wireModel="employeeSearch"
                placeholder="ابحث باسم الموظف"
                containerClass="relative"
                fieldClass="{{ $fieldClass }}"
            />

            <x-select-field label="نوع الخدمة" name="service_type" wireModel="serviceTypeFilter" :options="$serviceTypes->pluck('label', 'id')->toArray()"
                placeholder="جميع أنواع الخدمات" containerClass="relative" />

            <x-select-field label="المزود" name="provider" wireModel="providerFilter" :options="$providers->pluck('name', 'id')->toArray()"
                placeholder="جميع المزودين" containerClass="relative" />

            <x-select-field label="الحساب" name="account" wireModel="accountFilter" :options="$customers->pluck('name', 'id')->toArray()"
                placeholder="جميع الحسابات" containerClass="relative" />

            <div class="relative mt-1">
                <input type="date" name="start_date" id="start_date" wire:model.live="startDate" wire:change="$refresh"
                    placeholder=" " class="peer {{ $fieldClass }}" />
                <label for="start_date" class="{{ $labelClass }}">من تاريخ</label>
            </div>

            <div class="relative mt-1">
                <input type="date" name="end_date" id="end_date" wire:model.live="endDate" wire:change="$refresh"
                    placeholder=" " class="peer {{ $fieldClass }}" />
                <label for="end_date" class="{{ $labelClass }}">إلى تاريخ</label>
            </div>

            <x-input-field name="pnr" label="PNR" wireModel="pnrFilter" placeholder="بحث بـ PNR"
                containerClass="relative" fieldClass="{{ $fieldClass }}" />

            <x-input-field name="reference" label="المرجع" wireModel="referenceFilter" placeholder="بحث بالمرجع"
                containerClass="relative" fieldClass="{{ $fieldClass }}" />
        </div>

        <div class="flex flex-col md:flex-row justify-end items-center gap-2 mt-3">
            <button wire:click="resetFilters"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                إعادة تعيين الفلاتر
            </button>

            @can('accounts.export')
                <x-primary-button type="button" @click="open('excel')">تقرير Excel</x-primary-button>
            @endcan

            @can('accounts.print')
                <x-primary-button type="button" @click="open('pdf')">تقرير PDF</x-primary-button>
            @endcan
        </div>

        @if ($sales->isEmpty())
            <div class="text-center text-gray-400 py-6">لا توجد نتائج مطابقة للفلاتر المحددة</div>
        @endif

        <div class="h-6"></div>

        <div
            x-data="{
                selected: @entangle('selectedSales'),
                total: {{ $sales->count() }},
            }"
            x-init="
                const h = $refs.selectAll;
                const sync = () => {
                    const len = Array.isArray(selected) ? selected.length : (selected?.length ?? 0);
                    h.checked       = (len > 0 && len === total);
                    h.indeterminate = (len > 0 && len < total);
                };
                sync();
                $watch('selected', sync);
            "
            class="overflow-x-auto"
        >
            <table class="min-w-full border border-gray-200 rounded-xl overflow-hidden text-xs">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border-b text-center">
                            <input type="checkbox" x-ref="selectAll" @click.prevent="$wire.toggleSelectAll()"
                                class="rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]"
                                style="accent-color: rgb(var(--primary-500));" title="تحديد/إلغاء تحديد الكل">
                        </th>
                        <th class="p-2 border-b">الرقم</th>
                        @foreach ($columns as $col)
                            <th class="p-2 border-b">{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($sales as $sale)
                        @php $isRefund = in_array($sale->status ?? '', ['Refund-Full','Refund-Partial'], true); @endphp
                        <tr wire:key="row-{{ $sale->id }}">
                            <td class="p-2 border-b border-[rgba(0,0,0,0.07)] text-center">
                                <input type="checkbox"
                                    wire:key="cb-{{ $sale->id }}"
                                    wire:model.live="selectedSales"
                                    value="{{ (string) $sale->id }}"
                                    class="rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]"
                                    style="accent-color: rgb(var(--primary-500));">
                            </td>
                            <td class="p-2 border-b border-[rgba(0,0,0,0.07)] text-center">
                                {{ ($sales->currentPage() - 1) * $sales->perPage() + $loop->iteration }}
                            </td>

                            @foreach ($columns as $col)
                                @php
                                    $value  = data_get($sale, $col['key']);
                                    $format = $col['format'] ?? null;
                                    $color  = $col['color'] ?? null;
                                @endphp
                                <td class="p-2 border-b border-[rgba(0,0,0,0.07)] {{ $color ? 'text-' . $color : '' }}">
                                    @switch($format)
                                        @case('date')
                                            {{ $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '-' }}
                                        @break

                                        @case('money')
                                            {{ is_null($value) ? '-' : number_format($value, 2) }}
                                        @break

                                        @case('status')
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 {{ $isRefund ? 'text-red-600' : 'text-gray-600' }}">
                                                {{ strtoupper($value) }}
                                            </span>
                                        @break

                                        @case('custom')
                                            @can('accounts.invoice')
                                                @if($isRefund)
                                                    <span class="font-semibold text-red-400 cursor-not-allowed select-none" title="إشعار دائن للعرض فقط">
                                                        إشعار دائن
                                                    </span>
                                                @else
                                                    <button wire:click="openInvoiceModal({{ $sale->id }})"
                                                        class="font-semibold text-[rgb(var(--primary-600))] hover:text-black transition-colors duration-200">
                                                        فاتورة فردية
                                                    </button>
                                                @endif
                                            @endcan
                                        @break

                                        @default
                                            {{ $value ?? '-' }}
                                    @endswitch
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

        @if ($sales->hasPages())
            <div class="px-4 py-2 border-t border-gray-200">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show" x-transition
            class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
            style="background-color: rgb(var(--primary-500));">
            {{ session('message') }}
        </div>
    @endif

    {{-- ======= فاتورة فردية / إشعار دائن ======= --}}
    @if ($showInvoiceModal && $selectedSale)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 print:bg-white print:static print:overflow-visible" x-cloak>
            <div class="bg-white w-full max-w-2xl rounded-xl shadow-xl p-6 relative overflow-y-auto max-h-[90vh] print:shadow-none print:max-w-full print:p-0 print:overflow-visible">
                <button wire:click="$set('showInvoiceModal', false)"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold print:hidden">&times;</button>

                @if ($invoiceStep === 'tax')
                    <h2 class="text-xl font-bold text-center mb-5" style="color: rgb(var(--primary-700));">إدخال ضريبة الفاتورة</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-1">
                                الضريبة
                                <span class="text-gray-500 text-xs">
                                    ({{ $taxIsPercent ? '%' : ($selectedSale->agency->currency ?? $agencyCurrency) }})
                                </span>
                            </label>
                            <div class="flex gap-2">
                                <input type="number" step="0.01" min="0" wire:model.lazy="taxAmount"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <select wire:model="taxIsPercent"
                                    class="rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                    <option value="1">%</option>
                                    <option value="0">{{ $selectedSale->agency->currency ?? $agencyCurrency }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-end">
                            <x-primary-button wire:click="addTax" class="w-full" wire:loading.attr="disabled">إضافة</x-primary-button>
                        </div>
                    </div>

                    <div class="text-sm bg-gray-50 rounded-lg p-3">
                        <div class="grid grid-cols-2 gap-2">
                            <div><strong>المستفيد:</strong> {{ $selectedSale->beneficiary_name }}</div>
                            <div class="text-left"><strong>التاريخ:</strong> {{ $selectedSale->sale_date }}</div>
                            <div><strong>PNR:</strong> {{ $selectedSale->pnr ?? '-' }}</div>
                            <div class="text-left"><strong>المبلغ:</strong> {{ number_format($selectedSale->usd_sell,2) }} {{ $selectedSale->agency->currency ?? $agencyCurrency }}</div>
                        </div>
                    </div>
                @else
                    <h2 class="text-2xl font-bold text-center mb-4" style="color: rgb(var(--primary-700));">
                        {{ $isCreditNote ? 'إشعار دائن' : 'فاتورة العملية' }}
                    </h2>

                    <div class="flex justify-between text-sm border-b pb-3 mb-3">
                        <div class="text-right">
                            <div class="font-bold text-base">{{ $selectedSale->agency->name ?? 'AGENCY' }}</div>
                            <div>{{ $selectedSale->agency->address ?? 'العنوان غير متوفر' }}</div>
                            <div>{{ $selectedSale->agency->phone ?? '' }}</div>
                            <div>{{ $selectedSale->agency->email ?? '' }}</div>
                            @if(!empty($selectedSale->agency->tax_number))
                                <div>VAT: {{ $selectedSale->agency->tax_number }}</div>
                            @endif
                        </div>
                        <div class="text-left text-sm">
                            <p><strong>Order No:</strong> {{ $selectedSale->id }} / {{ now()->format('y') }}</p>
                            <p><strong>Date:</strong> {{ $selectedSale->sale_date }}</p>
                            <p><strong>Invoice No:</strong>
                                {{ ($isCreditNote ? 'CN-' : 'INV-') . str_pad($selectedSale->id, 5, '0', STR_PAD_LEFT) }}
                            </p>
                        </div>
                    </div>

                    <div class="mb-4 text-sm grid grid-cols-2 gap-y-1">
                        <p><strong>الاسم:</strong> {{ $selectedSale->beneficiary_name }}</p>
                        <p><strong>الهاتف:</strong> {{ $selectedSale->phone_number }}</p>
                        <p><strong>PNR:</strong> {{ $selectedSale->pnr ?? '-' }}</p>
                        <p><strong>المرجع:</strong> {{ $selectedSale->reference ?? '-' }}</p>
                    </div>

                    <table class="w-full text-sm border border-collapse border-gray-300 mb-4">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-2 py-1">Doc No</th>
                                <th class="border px-2 py-1">Passenger Name</th>
                                <th class="border px-2 py-1">Sector</th>
                                <th class="border px-2 py-1">Class</th>
                                <th class="border px-2 py-1">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border px-2 py-1">{{ $selectedSale->reference }}</td>
                                <td class="border px-2 py-1">{{ $selectedSale->beneficiary_name }}</td>
                                <td class="border px-2 py-1">{{ $selectedSale->route ?? '-' }}</td>
                                <td class="border px-2 py-1">{{ $selectedSale->service_class ?? 'S' }}</td>
                                <td class="border px-2 py-1 font-bold">
                                    {{ number_format($invoiceTotals['base'], 2) }} {{ $selectedSale->agency->currency ?? $agencyCurrency }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    @php
                        $currency = $selectedSale->agency->currency ?? $agencyCurrency;
                        $hasTax   = abs($invoiceTotals['tax'] ?? 0) > 0.00001;
                        $grossVal = $hasTax ? $invoiceTotals['net'] : $invoiceTotals['base'];
                    @endphp
                    <div class="text-sm text-right mb-3">
                        <p><strong>Currency:</strong> {{ $currency }}</p>
                        @if($hasTax)
                            <p><strong>TAX:</strong> {{ number_format($invoiceTotals['tax'], 2) }}</p>
                        @endif
                        <p><strong>Net:</strong> {{ number_format($invoiceTotals['base'], 2) }}</p>
                        <p><strong>Gross:</strong> {{ number_format($grossVal, 2) }}</p>
                        <p class="mt-2">Only {{ ucwords(\App\Helpers\NumberToWords::convert($grossVal)) }} {{ $currency }}</p>
                    </div>

                    <div class="flex items-center justify-between print:hidden">
                        <button class="underline text-sm" wire:click="editTax">تعديل الضريبة</button>
                        <button
                            @disabled(! $currentInvoiceId)
                            wire:click="downloadSingleInvoicePdf({{ $currentInvoiceId ?? 'null' }})"
                            class="text-white px-3 py-1 rounded-lg text-sm shadow transition duration-200 disabled:opacity-50"
                            style="background: linear-gradient(to right, rgb(var(--primary-500)), rgb(var(--primary-600)));"
                        >
                            تحميل PDF
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ======= فاتورة مجمّعة ======= --}}
    @if ($showBulkInvoiceModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-cloak>
            <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 relative">
                <button wire:click="$set('showBulkInvoiceModal', false)"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>

                <h2 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    إصدار فاتورة مجمعة
                </h2>

                <form wire:submit.prevent="createBulkInvoice" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">اسم الجهة</label>
                        <input type="text" wire:model.defer="invoiceEntityName"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                        @error('invoiceEntityName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">تاريخ الفاتورة</label>
                        <input type="date" wire:model.defer="invoiceDate"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                        @error('invoiceDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">
                            الضريبة (للفاتورة المجمّعة)
                            <span class="text-gray-500 text-xs">
                                ({{ $bulkTaxIsPercent ? '%' : $agencyCurrency }})
                            </span>
                        </label>
                        <div class="flex gap-2">
                            <input type="number" step="0.01" min="0" wire:model.lazy="bulkTaxAmount"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <select wire:model="bulkTaxIsPercent"
                                class="rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                <option value="1">%</option>
                                <option value="0">{{ $agencyCurrency }}</option>
                            </select>
                        </div>
                    </div>

                    @php
                        $__bulkTax  = $bulkTaxIsPercent ? round($bulkSubtotal * ($bulkTaxAmount/100), 2) : (float)$bulkTaxAmount;
                        $__bulkGrand = $bulkSubtotal + $__bulkTax;
                    @endphp
                    <div class="bg-gray-50 rounded-lg p-3 text-sm">
                        <div class="flex justify-between"><span>Subtotal:</span><span>{{ number_format($bulkSubtotal,2) }} {{ $agencyCurrency }}</span></div>
                        <div class="flex justify-between"><span>Tax:</span><span>{{ number_format($__bulkTax,2) }} {{ $agencyCurrency }}</span></div>
                        <div class="flex justify-between font-semibold"><span>Grand Total:</span><span>{{ number_format($__bulkGrand,2) }} {{ $agencyCurrency }}</span></div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" wire:click="$set('showBulkInvoiceModal', false)"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
                            إلغاء
                        </button>
                        <x-primary-button type="submit" wire:loading.attr="disabled">تأكيد وإنشاء الفاتورة</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ======= نوافذ التقارير ======= --}}
    <div
        x-cloak
        x-show="showReport"
        class="fixed inset-0 z-40 bg-black/10 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative">
            <button @click="close()" class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
            <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">اختر نوع التقرير</h3>
            <div class="flex flex-col gap-4">
                <x-primary-button type="button" @click="generateFull()" padding="px-6 py-3">تقرير كامل</x-primary-button>
                <x-primary-button type="button" @click="openFields()" padding="px-6 py-3">تقرير مخصص</x-primary-button>
                <button type="button" @click="close()"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-6 py-3 rounded-xl shadow transition duration-300 text-sm mt-4">
                    إلغاء
                </button>
            </div>
        </div>
    </div>

    <div
        x-cloak
        x-show="showFields"
        class="fixed inset-0 z-40 bg-black/10 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative">
            <button @click="closeFields()" class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
            <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">اختر حقول التقرير</h3>
            <form id="customReportForm" method="GET" target="_blank" @submit="prepareCustom">
                <div class="grid grid-cols-2 gap-4 max-h-96 overflow-y-auto p-2">
                    @foreach ([
                        'sale_date' => 'تاريخ البيع',
                        'beneficiary_name' => 'اسم المستفيد',
                        'customer' => 'العميل',
                        'serviceType' => 'الخدمة',
                        'provider' => 'المزود',
                        'usd_buy' => 'USD Buy',
                        'usd_sell' => 'USD Sell',
                        'amount_received' => 'المبلغ',
                        'account' => 'الحساب',
                        'reference' => 'المرجع',
                        'pnr' => 'PNR',
                        'route' => 'Route',
                        'user' => 'اسم الموظف',
                    ] as $field => $label)
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer">
                            <input type="checkbox" name="fields[]" value="{{ $field }}" checked
                                   class="h-4 w-4 rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]">
                            <span class="text-gray-700 text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-6 flex justify-center gap-3">
                    <button type="button" @click="closeFields()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-6 py-2 rounded-xl shadow transition duration-300 text-sm">
                        رجوع
                    </button>
                    <x-primary-button type="submit" padding="px-6 py-2">تحميل التقرير</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</div>

{{-- ======= سكربت Alpine لربط الفلاتر الحية وإرسالها ======= --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reportBox', () => ({
            // ربط حي مع Livewire
            employeeSearch: @entangle('employeeSearch'),
            serviceType:    @entangle('serviceTypeFilter'),
            provider:       @entangle('providerFilter'),
            account:        @entangle('accountFilter'),
            startDate:      @entangle('startDate'),
            endDate:        @entangle('endDate'),
            pnr:            @entangle('pnrFilter'),
            reference:      @entangle('referenceFilter'),

            currentReportType: '',
            showReport: false,
            showFields: false,

            open(type){ this.currentReportType = type; this.showReport = true; },
            close(){ this.showReport = false; },
            openFields(){ this.showReport = false; this.showFields = true; },
            closeFields(){ this.showFields = false; this.showReport = true; },

            params() {
                const o = {
                    employeeSearch: this.employeeSearch,
                    service_type:   this.serviceType,
                    provider:       this.provider,
                    account:        this.account,
                    start_date:     this.startDate,
                    end_date:       this.endDate,
                    pnr:            this.pnr,
                    reference:      this.reference,
                };
                const p = new URLSearchParams();
                Object.entries(o).forEach(([k,v])=>{
                    if(v!==null && v!==undefined && String(v).trim()!=='') p.append(k, v);
                });
                return p.toString();
            },

            generateFull() {
                const base = this.currentReportType === 'pdf'
                    ? '/agency/accounts/report/pdf'
                    : '/agency/accounts/report/excel';
                window.open(`${base}?${this.params()}`, '_blank');
                this.close();
            },

            prepareCustom(e){
                e.preventDefault();
                const form = document.getElementById('customReportForm');
                [...form.querySelectorAll('input[type=hidden]')].forEach(n=>n.remove());
                const add = (k,v)=>{ if(v!==null && v!==undefined && String(v).trim()!==''){ 
                    const i=document.createElement('input'); i.type='hidden'; i.name=k; i.value=v; form.appendChild(i);
                }};
                const q = Object.fromEntries(new URLSearchParams(this.params()));
                Object.entries(q).forEach(([k,v])=>add(k,v));
                form.action = this.currentReportType === 'pdf'
                    ? '/agency/accounts/report/pdf'
                    : '/agency/accounts/report/excel';
                form.submit();
                this.showFields = false;
            },

            // اختياري: عند تغيير نوع الخدمة امسح فلتر الموظف لتجنب لبس النتائج
            // $watch('serviceType', () => { this.employeeSearch = ''; }),
        }));
    });
</script>
