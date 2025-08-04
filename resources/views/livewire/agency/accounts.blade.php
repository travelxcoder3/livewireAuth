@php
    use App\Services\ThemeService;
    use App\Tables\AccountTable;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);

    $columns = AccountTable::columns(); // ⬅️ هذا السطر الجديد
    $fieldClass =
        'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
    $labelClass =
        'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
    $containerClass = 'relative mt-1';
    $agencyCurrency = Auth::user()?->agency?->currency ?? 'USD';
@endphp



<div class="space-y-6">


    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة الحسابات
        </h2>
        <div class="flex items-center gap-2">
            <label class="text-sm font-semibold text-gray-700">الإجمالي:</label>
            <input type="text" value="{{ number_format($totalSales, 2) }}" readonly
                class="bg-gray-100 border border-gray-300 rounded px-3 py-1 text-sm text-gray-700 w-32 text-center">
        </div>
        @can('accounts.invoice')

        @if ($sales->count())
<x-primary-button wire:click="openBulkInvoiceModal" class="ml-2">
    إصدار فاتورة مجمعة
</x-primary-button>

        @endif
        @endcan

    </div>

  <!-- فلاتر البحث -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4">
            <x-input-field name="search" label="بحث عام" wireModel="search" placeholder="ابحث في جميع الحقول..."
                containerClass="relative" fieldClass="{{ $fieldClass }}" />

            <!-- نوع الخدمة -->
            <x-select-field label="نوع الخدمة" name="service_type" wireModel="serviceTypeFilter" :options="$serviceTypes->pluck('label', 'id')->toArray()"
                placeholder="جميع أنواع الخدمات" containerClass="relative" />

            <!-- المزود -->
            <x-select-field label="المزود" name="provider" wireModel="providerFilter" :options="$providers->pluck('name', 'id')->toArray()"
                placeholder="جميع المزودين" containerClass="relative" />
            <!-- الحساب -->
            <x-select-field label="الحساب" name="account" wireModel="accountFilter" :options="$customers->pluck('name', 'id')->toArray()"
                placeholder="جميع الحسابات" containerClass="relative" />
            <!-- من تاريخ -->
            <div class="relative mt-1">
                <input type="date" name="start_date" id="start_date" wire:model="startDate" wire:change="$refresh"
                    placeholder=" " {{-- ضروري لعمل floating label --}} class="peer {{ $fieldClass }}" />
                <label for="start_date" class="{{ $labelClass }}">
                    من تاريخ
                </label>
            </div>
            <!-- إلى تاريخ -->
            <div class="relative mt-1">
                <input type="date" name="end_date" id="end_date" wire:model="endDate" wire:change="$refresh"
                    placeholder=" " class="peer {{ $fieldClass }}" />
                <label for="end_date" class="{{ $labelClass }}">
                    إلى تاريخ
                </label>
            </div>
            <!-- PNR -->
            <x-input-field name="pnr" label="PNR" wireModel="pnrFilter" placeholder="بحث بـ PNR"
                containerClass="relative" fieldClass="{{ $fieldClass }}" />

            <!-- المرجع -->
            <x-input-field name="reference" label="المرجع" wireModel="referenceFilter" placeholder="بحث بالمرجع"
                containerClass="relative" fieldClass="{{ $fieldClass }}" />
        </div>

        <div class="flex flex-col md:flex-row justify-end items-center gap-2 mt-3">

            <!-- زر إعادة تعيين الفلاتر -->
            <button wire:click="resetFilters"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                إعادة تعيين الفلاتر
            </button>

            <!-- زر تصدير إكسل -->
            @can('accounts.export')
    <x-primary-button type="button" onclick="openReportModal('excel')">
        تقرير Excel
    </x-primary-button>
            @endcan

            <!-- زر طباعة PDF -->
            @can('accounts.print')
    <x-primary-button type="button" onclick="openReportModal('pdf')">
        تقرير PDF
    </x-primary-button>
            @endcan


        </div>


        <!-- رسالة لا توجد نتائج -->
        @if ($sales->isEmpty())
            <div class="text-center text-gray-400 py-6">
                لا توجد نتائج مطابقة للفلاتر المحددة
            </div>
        @endif

        <!-- جدول العمليات -->
        <div class="h-6"></div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200 rounded-xl overflow-hidden text-xs">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border-b text-center">
                        <input type="checkbox"
       wire:model="selectAll"
       wire:change="toggleSelectAll"
       class="form-checkbox h-4 w-4 text-green-600">


                        </th>
                        <th class="p-2 border-b">الرقم</th>

                        @foreach ($columns as $col)
                            <th class="p-2 border-b">{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($sales as $sale)
                        <tr>
                            <td class="p-2 border-b text-center">
                            <input type="checkbox"
       value="{{ $sale->id }}"
       wire:model="selectedSales"
       class="form-checkbox h-4 w-4 text-green-600">


                            </td>
                            <td class="p-2 border-b text-center">
                                {{ ($sales->currentPage() - 1) * $sales->perPage() + $loop->iteration }}
                            </td>


                            @foreach ($columns as $col)
                                @php
                                    $value = data_get($sale, $col['key']);
                                    $format = $col['format'] ?? null;
                                    $color = $col['color'] ?? null;
                                @endphp

                                <td class="p-2 border-b {{ $color ? 'text-' . $color : '' }}">
                                    @switch($format)
                                        @case('date')
                                            {{ \Carbon\Carbon::parse($value)->format('Y-m-d') }}
                                        @break

                                        @case('money')
                                            {{ number_format($value, 2) }}
                                        @break

                                        @case('status')
                                            <span
                                                class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                {{ strtoupper($value) }}
                                            </span>
                                        @break

                                        @case('custom')
                                        @can('accounts.invoice')
                                            <button wire:click="openInvoiceModal({{ $sale->id }})" class="font-semibold"
                                                style="color: rgb(var(--primary-600));">
                                                فاتورة فردية
                                            </button>
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

        <!-- Pagination -->
        @if ($sales->hasPages())
            <div class="px-4 py-2 border-t border-gray-200">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    <!-- رسائل النظام -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show" x-transition
            class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
            style="background-color: rgb(var(--primary-500));">
            {{ session('message') }}
        </div>
    @endif

    <!-- تأكيد الحذف -->
    {{-- @include('livewire.confirmation-modal') --}}
    <!-- نافذة اختيار نوع التقرير -->
    <div id="reportModal"
        class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center hidden backdrop-blur-sm">
        <div
            class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
            <button onclick="closeReportModal()"
                class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                &times;
            </button>

            <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                اختر نوع التقرير
            </h3>

            <div class="flex flex-col gap-4">
                <input type="hidden" id="reportType">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">

                <x-primary-button type="button" onclick="generateFullReport()" padding="px-6 py-3">
                    تقرير كامل
                </x-primary-button>


                <x-primary-button type="button" onclick="openFieldsModal()" padding="px-6 py-3">
                    تقرير مخصص
                </x-primary-button>



                <button type="button" onclick="closeReportModal()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-6 py-3 rounded-xl shadow transition
                    duration-300 text-sm mt-4">
                    إلغاء
                </button>
            </div>
        </div>
    </div>

    <!-- نافذة اختيار الحقول -->
    <div id="fieldsModal"
        class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center hidden backdrop-blur-sm">
        <div
            class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
            <button onclick="closeFieldsModal()"
                class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                &times;
            </button>

            <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                اختر حقول التقرير
            </h3>

            <form id="customReportForm" method="GET" target="_blank" onsubmit="prepareCustomReport()">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">

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
                        <div class="flex items-center">
                            <label class="flex items-center space-x-2 space-x-reverse cursor-pointer">
                                <input type="checkbox" name="fields[]" value="{{ $field }}" checked
                                    class="h-4 w-4 rounded border-gray-300 focus:ring-[rgb(var(--primary-500))] text-[rgb(var(--primary-500))] accent-[rgb(var(--primary-500))]" />
                                <span class="text-gray-700 text-sm">{{ $label }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-center gap-3">
                    <button type="button" onclick="closeFieldsModal()"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-6 py-2 rounded-xl shadow transition
                        duration-300 text-sm">
                        رجوع
                    </button>
                        <x-primary-button type="submit" padding="px-6 py-2">
                            تحميل التقرير
                        </x-primary-button>

                </div>
            </form>
        </div>
    </div>

    @if ($showInvoiceModal && $selectedSale)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 print:bg-white print:static print:overflow-visible">
            <div
                class="bg-white w-full max-w-2xl rounded-xl shadow-xl p-6 relative overflow-y-auto max-h-[90vh] print:shadow-none print:max-w-full print:p-0 print:overflow-visible">
                <!-- زر الإغلاق -->
                <button wire:click="$set('showInvoiceModal', false)"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold print:hidden">
                    &times;
                </button>

                <!-- عنوان -->
                <h2 class="text-2xl font-bold text-center mb-4" style="color: rgb(var(--primary-700));">فاتورة العملية
                </h2>

                <!-- رأس الفاتورة -->
                <div class="flex justify-between text-sm border-b pb-3 mb-3">
                    <div class="text-right">
                        <div class="font-bold text-base">ATHKA HOLIDAYS</div>
                        <div>صنعاء، شارع الجزائر تقاطع الستين</div>
                        <div>+967-1-206166</div>
                    </div>
                    <div class="text-left text-sm">
                        <p><strong>Order No:</strong> {{ $selectedSale->id }} / {{ now()->format('y') }}</p>
                        <p><strong>Date:</strong> {{ $selectedSale->sale_date }}</p>
                        <p><strong>Invoice No:</strong> {{ 'INV-' . str_pad($selectedSale->id, 5, '0', STR_PAD_LEFT) }}
                        </p>
                    </div>
                </div>

                <!-- بيانات العميل -->
                <div class="mb-4 text-sm">
                    <p><strong>الاسم:</strong> {{ $selectedSale->beneficiary_name }}</p>
                    <p><strong>الهاتف:</strong> {{ $selectedSale->phone_number }}</p>
                    <p><strong>PNR:</strong> {{ $selectedSale->pnr ?? '-' }}</p>
                    <p><strong>المرجع:</strong> {{ $selectedSale->reference ?? '-' }}</p>
                </div>

                <!-- تفاصيل العملية -->
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
                            <td class="border px-2 py-1 font-bold">{{ number_format($selectedSale->usd_sell, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- الإجمالي -->
                <div class="text-sm text-right mb-3">
                    <p><strong>Currency:</strong> {{ $agencyCurrency }}</p>
                    <p><strong>TAX:</strong> 0.00</p>
                    <p><strong>Net:</strong> {{ number_format($selectedSale->usd_sell, 2) }}</p>
                    <p><strong>Gross:</strong> {{ number_format($selectedSale->usd_sell, 2) }}</p>
                    <p class="mt-2">Only {{ ucwords(\App\Helpers\NumberToWords::convert($selectedSale->usd_sell)) }}
                    {{ $agencyCurrency }}</p>
                </div>

                <!-- التواقيع -->
                <div class="flex justify-between text-sm mt-6">
                    <div class="text-center w-1/2">
                        <p>Prepared by</p>
                        <div class="border-t mt-8 border-gray-400 w-3/4 mx-auto"></div>
                    </div>
                    <div class="text-center w-1/2">
                        <p>Approved by</p>
                        <div class="border-t mt-8 border-gray-400 w-3/4 mx-auto"></div>
                    </div>
                </div>

                <!-- زر الطباعة -->
                <div class="mt-6 text-center print:hidden">
                    <button wire:click="downloadInvoicePdf({{ $selectedSale->id }})"
                        class="text-white px-3 py-1 rounded-lg text-sm shadow transition duration-200"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)), rgb(var(--primary-600)));">
                        تحميل PDF
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showBulkInvoiceModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 relative">
                <button wire:click="$set('showBulkInvoiceModal', false)"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
                <h2 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">إصدار فاتورة
                    مجمعة</h2>
                <form wire:submit.prevent="createBulkInvoice" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">اسم الجهة</label>
                        <input type="text" wire:model.defer="invoiceEntityName"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                        @error('invoiceEntityName')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">تاريخ الفاتورة</label>
                        <input type="date" wire:model.defer="invoiceDate"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                        @error('invoiceDate')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" wire:click="$set('showBulkInvoiceModal', false)"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">إلغاء</button>
                        <x-primary-button type="submit">
                            تأكيد وإنشاء الفاتورة
                        </x-primary-button>

                    </div>
                </form>
            </div>
        </div>
    @endif


    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .print-area,
            .print-area * {
                visibility: visible;
            }

            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>


    <script>
        let currentReportType = '';

        function openReportModal(type) {
            currentReportType = type;
            const modal = document.getElementById('reportModal');
            const content = modal.querySelector('.bg-white');

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('opacity-0', 'scale-95');
                content.classList.add('opacity-100', 'scale-100');
            }, 10);
        }

        function closeReportModal() {
            const modal = document.getElementById('reportModal');
            const content = modal.querySelector('.bg-white');

            content.classList.remove('opacity-100', 'scale-100');
            content.classList.add('opacity-0', 'scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function openFieldsModal() {
            closeReportModal();

            const modal = document.getElementById('fieldsModal');
            const content = modal.querySelector('.bg-white');

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('opacity-0', 'scale-95');
                content.classList.add('opacity-100', 'scale-100');
            }, 10);
        }

        function closeFieldsModal() {
            const modal = document.getElementById('fieldsModal');
            const content = modal.querySelector('.bg-white');

            content.classList.remove('opacity-100', 'scale-100');
            content.classList.add('opacity-0', 'scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
                openReportModal(currentReportType);
            }, 300);
        }

        function generateFullReport() {
            const startDate = "{{ $startDate }}";
            const endDate = "{{ $endDate }}";
            const serviceTypeFilter = "{{ $serviceTypeFilter }}";
            const providerFilter = "{{ $providerFilter }}";
            const accountFilter = "{{ $accountFilter }}";
            const pnrFilter = "{{ $pnrFilter }}";
            const referenceFilter = "{{ $referenceFilter }}";

            if (currentReportType === 'pdf') {
                window.open(
                    `/agency/accounts/report/pdf?start_date=${startDate}&end_date=${endDate}&service_type=${serviceTypeFilter}&provider=${providerFilter}&account=${accountFilter}&pnr=${pnrFilter}&reference=${referenceFilter}`,
                    '_blank');
            } else {
                window.open(
                    `/agency/accounts/report/excel?start_date=${startDate}&end_date=${endDate}&service_type=${serviceTypeFilter}&provider=${providerFilter}&account=${accountFilter}&pnr=${pnrFilter}&reference=${referenceFilter}`,
                    '_blank');
            }

            closeReportModal();
        }

        function prepareCustomReport() {
            event.preventDefault();
            const form = document.getElementById('customReportForm');
            const startDate = "{{ $startDate }}";
            const endDate = "{{ $endDate }}";
            const serviceTypeFilter = "{{ $serviceTypeFilter }}";
            const providerFilter = "{{ $providerFilter }}";
            const accountFilter = "{{ $accountFilter }}";
            const pnrFilter = "{{ $pnrFilter }}";
            const referenceFilter = "{{ $referenceFilter }}";

            // إضافة الفلاتر إلى النموذج
            const addHiddenInput = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            };

            addHiddenInput('service_type', serviceTypeFilter);
            addHiddenInput('provider', providerFilter);
            addHiddenInput('account', accountFilter);
            addHiddenInput('pnr', pnrFilter);
            addHiddenInput('reference', referenceFilter);

            if (currentReportType === 'pdf') {
                form.action = "/agency/accounts/report/pdf";
            } else {
                form.action = "/agency/accounts/report/excel";
            }

            form.submit();
            closeFieldsModal();
            closeReportModal();
        }
    </script>
</div>
