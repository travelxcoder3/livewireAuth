@php
use App\Tables\SalesTable;
$columns = SalesTable::columns();

$fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
$labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
$containerClass = 'relative mt-1';
@endphp

<div>
    <div>
        <div class="space-y-6">
            <!-- الصف العلوي -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">
                <!-- نوع الخدمة + الحالة -->
                <div class="lg:col-span-3 grid grid-cols-2 gap-4 items-end">
                    <!-- نوع الخدمة -->
                    <x-select-field
                        wireModel="service_type_id"
                        label="نوع الخدمة"
                        placeholder="اختر نوع الخدمة"
                        :options="$services->pluck('label', 'id')->toArray()"
                        containerClass="{{ $containerClass }}"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('service_type_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- الحالة -->
                    <x-select-field
                        wireModel="status"
                        label="الحالة"
                        placeholder="الحالة"
                        :options="['paid' => 'مدفوع', 'unpaid' => 'غير مدفوع']"
                        containerClass="{{ $containerClass }}"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('status') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- كارد الإحصائيات -->
                <div class="lg:col-span-6">
                    <div class="bg-white rounded-xl shadow-md border px-6 py-3 flex justify-center gap-x-4 items-center text-xs font-semibold text-gray-700 whitespace-nowrap mx-auto">
                        <div class="flex items-center gap-1">
                            <span class="text-[rgb(var(--primary-600))]">إجمالي:</span>
                            <span>{{ number_format($totalAmount, 2) }} {{ $currency }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-[rgb(var(--primary-600))]">محصلة:</span>
                            <span>{{ number_format($totalReceived, 2) }} {{ $currency }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-[rgb(var(--primary-600))]">آجلة:</span>
                            <span>{{ number_format($totalPending, 2) }} {{ $currency }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-[rgb(var(--primary-600))]">العمولة:</span>
                            <span>{{ number_format($sales->sum('commission'), 2) }} {{ $currency }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-[rgb(var(--primary-600))]">الربح:</span>
                            <span>{{ number_format($totalProfit, 2) }} {{ $currency }}</span>
                        </div>
                    </div>
                </div>

                <!-- الأزرار -->
                <div class="lg:col-span-3 flex justify-end gap-2">
                    @can('sales.reports.view')
                    <button type="button" onclick="openReportModal('pdf')"
                        class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        تقرير PDF
                    </button>
                    <button type="button" onclick="openReportModal('excel')"
                        class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        تقرير Excel
                    </button>
                    @endcan
                </div>
            </div>
        </div>

        @can('sales.create')
        <!-- نموذج الإضافة -->
        <div class="bg-white rounded-xl shadow-md p-4">
            <form wire:submit.prevent="save" class="space-y-4 text-sm" id="mainForm">
                <!-- السطر الأول -->
                <div class="grid grid-cols-12 gap-3">
                    <!-- اسم المستفيد -->
                    <x-input-field
                        wireModel="beneficiary_name"
                        label="اسم المستفيد"
                        placeholder="ادخل اسم المستفيد"
                        containerClass="col-span-3 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('beneficiary_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- المسار -->
                    <x-input-field
                        wireModel="route"
                        label="المسار / التفاصيل"
                        placeholder="المسار / التفاصيل"
                        containerClass="col-span-3 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('route') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- طريقة الدفع -->
                    <x-select-field
                        wireModel="payment_method"
                        label="طريقة الدفع"
                        placeholder="اختر طريقة الدفع"
                        :options="['cash' => 'كاش', 'transfer' => 'حوالة']"
                        containerClass="col-span-3 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('payment_method') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- اسم المودع -->
                    <x-input-field
                        wireModel="depositor_name"
                        label="اسم المودع"
                        placeholder="اسم المودع"
                        containerClass="col-span-3 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('depositor_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-24 gap-3">
                    <!-- الرقم -->
                    <x-input-field
                        wireModel="receipt_number"
                        label="رقم السند"
                        placeholder="رقم السند"
                        containerClass="col-span-2 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('receipt_number') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- تاريخ البيع -->
                    <x-input-field
                        type="date"
                        wireModel="sale_date"
                        label="تاريخ البيع"
                        placeholder="تاريخ البيع"
                        containerClass="col-span-4 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('sale_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- PNR -->
                    <x-input-field
                        wireModel="pnr"
                        label="PNR"
                        placeholder="PNR"
                        containerClass="col-span-3 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('pnr') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- المرجع -->
                    <x-input-field
                        wireModel="reference"
                        label="المرجع"
                        placeholder="المرجع"
                        containerClass="col-span-3 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('reference') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- وسيلة الدفع -->
                    <x-input-field
                        wireModel="payment_type"
                        label="وسيلة الدفع"
                        placeholder="وسيلة الدفع"
                        containerClass="col-span-6 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('payment_type') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- رقم الهاتف -->
                    <x-input-field
                        wireModel="phone_number"
                        label="رقم الهاتف"
                        placeholder="رقم الهاتف"
                        containerClass="col-span-6 relative"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('phone_number') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- الصف الثالث -->
                <div class="grid md:grid-cols-4 gap-3">
                    <!-- العميل عبر -->
                    <x-select-field
                        wireModel="intermediary_id"
                        label="العميل عبر"
                        placeholder="العميل عبر"
                        :options="$intermediaries->pluck('name', 'id')->toArray()"
                        containerClass="{{ $containerClass }}"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('intermediary_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- المزود -->
                    <x-select-field
                        wireModel="provider_id"
                        label="المزود"
                        placeholder="اختر المزود"
                        :options="$providers->pluck('name', 'id')->toArray()"
                        containerClass="{{ $containerClass }}"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('provider_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- العميل -->
                    <x-select-field
                        wireModel="customer_id"
                        label="العميل"
                        placeholder="اختر العميل"
                        :options="$customers->pluck('name', 'id')->toArray()"
                        containerClass="{{ $containerClass }}"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('customer_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- الحساب -->
                    <x-select-field
                        wireModel="account_id"
                        label="الحساب"
                        placeholder="اختر الحساب"
                        :options="$accounts->pluck('name', 'id')->toArray()"
                        containerClass="{{ $containerClass }}"
                        fieldClass="{{ $fieldClass }}"
                        labelClass="{{ $labelClass }}"
                    />
                    @error('account_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- الصف الرابع -->
                <div class="grid grid-cols-12 gap-3 items-end">
                    <!-- USD Buy -->
                    <x-input-field
                        wireModel="usd_buy"
                        label="USD Buy"
                        type="number"
                        placeholder="USD Buy"
                        containerClass="col-span-1 relative"
                        fieldClass="{{ $fieldClass }} text-sm py-1 px-2"
                        labelClass="{{ $labelClass }}"
                        height="h-9"
                        wireChange="calculateProfit"
                        step="0.01"
                    />
                    @error('usd_buy') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- USD Sell -->
                    <x-input-field
                        wireModel="usd_sell"
                        label="USD Sell"
                        type="number"
                        placeholder="USD Sell"
                        containerClass="col-span-1 relative"
                        fieldClass="{{ $fieldClass }} text-sm py-1 px-2"
                        labelClass="{{ $labelClass }}"
                        height="h-9"
                        wireChange="calculateProfit"
                        step="0.01"
                    />
                    @error('usd_sell') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- العمولة -->
                    <x-input-field
                        wireModel="commission"
                        label="العمولة"
                        type="number"
                        placeholder="العمولة"
                        containerClass="col-span-1 relative"
                        fieldClass="{{ $fieldClass }} text-sm py-1 px-2"
                        labelClass="{{ $labelClass }}"
                        height="h-9"
                        step="0.01"
                    />
                    @error('commission') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                    <!-- الربح + المبلغ المدفوع + المتبقي -->
                    <div class="col-span-4 flex items-end gap-4">
                        <!-- الربح -->
                        <div class="text-xs font-semibold text-[rgb(var(--primary-600))]">
                            <span>الربح:</span>
                            <span>{{ number_format($sale_profit, 2) }}</span>
                        </div>

                        <!-- المبلغ المدفوع -->
                        <x-input-field
                            wireModel="amount_paid"
                            label="المبلغ المدفوع"
                            type="number"
                            placeholder="المبلغ المدفوع"
                            containerClass="relative w-full max-w-xs"
                            fieldClass="{{ $fieldClass }} text-sm py-1 px-2"
                            labelClass="{{ $labelClass }}"
                            height="h-9"
                            wireChange="calculateDue"
                            step="0.01"
                        />
                        @error('amount_paid') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                        <!-- المتبقي -->
                        <div class="text-xs font-semibold text-[rgb(var(--primary-600))]">
                            <span>المتبقي:</span>
                            <span>{{ number_format($amount_due, 2) }}</span>
                        </div>
                    </div>

                    <!-- مساحة فارغة -->
                    <div class="col-span-2"></div>

                    <!-- الأزرار -->
                    <div class="col-span-3 flex flex-row gap-3 items-end justify-end">
                        <button wire:click="resetFields"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                            تنظيف الحقول
                        </button>

                        <button type="submit"
                                class="text-white font-bold px-4 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm"
                                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                            حفظ العملية
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @endcan

        <!-- نافذة اختيار نوع التقرير -->
        <div id="reportModal" class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center hidden backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
                <button onclick="closeReportModal()"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                    &times;
                </button>

                <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    اختر نوع التقرير
                </h3>
                
                <div class="flex flex-col gap-4">
                    <input type="hidden" id="reportType">
                    <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                    <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                    
                    <button type="button" onclick="generateFullReport()"
                        class="text-white font-bold px-6 py-3 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        تقرير كامل
                    </button>
                    
                    <button type="button" onclick="openFieldsModal()"
                        class="text-white font-bold px-6 py-3 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        تقرير مخصص
                    </button>

                    <button type="button" onclick="closeReportModal()"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-6 py-3 rounded-xl shadow transition 
                            duration-300 text-sm mt-4">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>

        <!-- نافذة اختيار الحقول -->
        <div id="fieldsModal" class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center hidden backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
                <button onclick="closeFieldsModal()"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                    &times;
                </button>

                <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    اختر حقول التقرير
                </h3>
                
                <form id="customReportForm" method="GET" target="_blank" onsubmit="prepareCustomReport()">
                    <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                    <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                    
                    <div class="grid grid-cols-2 gap-4 max-h-96 overflow-y-auto p-2">
                        @foreach([
                            'sale_date' => 'التاريخ',
                            'beneficiary_name' => 'المستفيد',
                            'customer' => 'العميل',
                            'serviceType' => 'الخدمة',
                            'provider' => 'المزود',
                            'intermediary' => 'الوسيط',
                            'usd_buy' => 'USD Buy',
                            'usd_sell' => 'USD Sell',
                            'sale_profit' => 'الربح',
                            'amount_paid' => 'المبلغ',
                            'account' => 'الحساب',
                            'reference' => 'المرجع',
                            'pnr' => 'PNR',
                            'route' => 'Route',
                            'status' => 'الحالة',
                            'user' => 'اسم الموظف',
                            'commission' => 'العمولة'
                        ] as $field => $label)
                        <div class="flex items-center">
                            <label class="flex items-center space-x-2 space-x-reverse cursor-pointer">
                                <input type="checkbox"
                                    name="fields[]"
                                    value="{{ $field }}"
                                    checked
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
                        <button type="submit"
                            class="text-white font-bold px-6 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm"
                            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                            تحميل التقرير
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- جدول المبيعات -->
        <x-data-table :rows="$sales" :columns="$columns" />

        <!-- رسائل النظام -->
        @if(session()->has('message'))
            <div x-data="{ show: true }"
                 x-init="setTimeout(() => show = false, 2000)"
                 x-show="show"
                 x-transition
                 class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm" 
                 style="background-color: rgb(var(--primary-500));">
                {{ session('message') }}
            </div>
        @endif
    </div>
</div>

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
        const startDate = "{{ request('start_date') }}";
        const endDate = "{{ request('end_date') }}";
        
        if (currentReportType === 'pdf') {
            window.open(`{{ route('agency.sales.report.pdf') }}?start_date=${startDate}&end_date=${endDate}`, '_blank');
        } else {
            window.open(`{{ route('agency.sales.report.excel') }}?start_date=${startDate}&end_date=${endDate}`, '_blank');
        }
        
        closeReportModal();
    }

    function prepareCustomReport() {
        event.preventDefault();
        const form = document.getElementById('customReportForm');
        const startDate = "{{ request('start_date') }}";
        const endDate = "{{ request('end_date') }}";
        
        if (currentReportType === 'pdf') {
            form.action = "{{ route('agency.sales.report.pdf') }}";
        } else {
            form.action = "{{ route('agency.sales.report.excel') }}";
        }
        
        form.submit();
        closeFieldsModal();
        closeReportModal();
    }
</script>