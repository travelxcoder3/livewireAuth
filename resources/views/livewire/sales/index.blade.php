@php
use App\Tables\SalesTable;
$columns = SalesTable::columns();
@endphp

<div>
    <div>
        <!-- رسائل النجاح والخطأ -->
        @if($successMessage)
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg text-center">
                {{ $successMessage }}
            </div>
        @endif

        @error('general')
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg text-center">
                {{ $message }}
            </div>
        @enderror

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

   <div class="mb-6 grid grid-cols-12 gap-4 items-center">
    <!-- العنوان في اليسار -->
    <div class="col-span-3 flex items-center justify-start">
        <h1 class="text-2xl font-bold text-[rgb(var(--primary-700))]">إدارة المبيعات</h1>
    </div>

    <!-- الكروت داخل كارد رئيسي في الوسط -->
    <div class="col-span-6">
        <div class="bg-white border shadow rounded-xl px-4 py-2 flex justify-center gap-x-4 items-center text-xs font-semibold text-gray-700 whitespace-nowrap">
            <!-- الربح -->
            <div class="flex flex-col items-center px-2">
                <span class="text-[rgb(var(--primary-600))]">الربح</span>
                <span>{{ number_format($totalProfit, 2) }} {{ $currency }}</span>
            </div>
            <!-- العمولة -->
            <div class="flex flex-col items-center px-2">
                <span class="text-[rgb(var(--primary-600))]">العمولة</span>
                <span>{{ number_format($sales->sum('commission'), 2) }} {{ $currency }}</span>
            </div>
            <!-- العمولة المستحقة -->
            <div class="flex flex-col items-center px-2">
                <span class="text-[rgb(var(--primary-600))]">العمولة المستحقة</span>
                <span>{{ number_format($totalPending, 2) }} {{ $currency }}</span>
            </div>
        </div>
    </div>

    <!-- كارد الإحصائيات المالية في اليمين -->
    <div class="col-span-3">
        <div class="bg-white border shadow rounded-xl px-4 py-2 flex justify-center gap-x-4 items-center text-xs font-semibold text-gray-700 whitespace-nowrap">
            <div class="flex flex-col items-center px-2">
                <span class="text-[rgb(var(--primary-600))]">الإجمالي</span>
                <span>{{ number_format($totalAmount, 2) }} {{ $currency }}</span>
            </div>
            <div class="flex flex-col items-center px-2">
                <span class="text-[rgb(var(--primary-600))]">المحصلة</span>
                <span>{{ number_format($totalReceived, 2) }} {{ $currency }}</span>
            </div>
            <div class="flex flex-col items-center px-2">
                <span class="text-[rgb(var(--primary-600))]">الآجلة</span>
                <span>{{ number_format($totalPending, 2) }} {{ $currency }}</span>
            </div>
        </div>
    </div>
</div>


        <div class="space-y-6">
            <!-- الصف العلوي -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">
                <!-- نوع الخدمة + الحالة -->
                <div class="lg:col-span-4 grid grid-cols-2 gap-4 items-end">
                    <!-- نوع الخدمة -->
                        <x-select-field
                            wireModel="service_type_id"
                            label="نوع الخدمة"
                            :options="$services->pluck('label', 'id')->toArray()"
                            placeholder="اسم الخدمة"
                            fieldClass="peer rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm ..."
                            labelClass="..."
                        />


                    <!-- الحالة -->
                    <x-select-field
                        wireModel="status"
                        label="الحالة"
                        :options="[
                            'issued' => 'تم الإصدار',
                            'refunded' => 'تم الاسترداد',
                            'canceled' => 'تم الإلغاء',
                            'pending' => 'قيد الانتظار',
                            'reissued' => 'إعادة الإصدار',
                            'void' => 'لاغية',
                            'paid' => 'مدفوعة',
                            'unpaid' => 'غير مدفوعة'
                        ]"
                        placeholder=" الحالة"
                        containerClass="relative mt-1"
                        fieldClass="peer rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm placeholder-transparent text-gray-600 
                                    focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] transition duration-200"
                        labelClass="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all
                                    peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]"
                    />
                  
                
               </div>
                 <!-- المساحة الفارغة -->
                <div class="lg:col-span-5"></div>

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
                        name="beneficiary_name"
                        label="اسم المستفيد"
                        wireModel="beneficiary_name"
                        placeholder="اسم المستفيد"
                        containerClass="relative mt-1 col-span-3"
                        errorName="beneficiary_name"
                                        />
                
                      
                    <!-- المسار -->
                    <x-input-field
                        name="route"
                        label="المسار / التفاصيل"
                        wireModel="route"
                        placeholder="المسار / التفاصيل"
                        containerClass="relative mt-1 col-span-3"
                        errorName="route"
                    />
                  
                    <!-- طريقة الدفع -->
                    <x-select-field
                        wireModel="payment_method"
                        label="طريقة الدفع"
                        :options="[
                            'kash' => 'كاش',
                            'part' => 'جزئي',
                            'all' => 'كامل جزئي'
                        ]"
                        placeholder=" طريقة الدفع"
                        containerClass="relative mt-1 col-span-3"
                        errorName="payment_method"
                    />

                    <!-- اسم المودع -->
                    <x-input-field
                        name="depositor_name"
                        label="اسم المودع"
                        wireModel="depositor_name"
                        placeholder="اسم المودع"
                        containerClass="relative mt-1 col-span-3"
                        errorName="depositor_name"
                    />
                </div>

                <div class="grid grid-cols-24 gap-3">
                    <!-- الرقم -->
                    <x-input-field
                        name="phone_number"
                        label="رقم الهاتف"
                        wireModel="phone_number"
                        placeholder="رقم الهاتف"
                        type="text"
                        containerClass="relative mt-1 col-span-3"
                        errorName="phone_number"
                    />

                    <!-- تاريخ البيع -->
                    <x-input-field
                        name="sale_date"
                        label="تاريخ البيع"
                        wireModel="sale_date"
                        placeholder="تاريخ البيع"
                        type="date"
                        containerClass="relative mt-1 col-span-3"
                        errorName="sale_date"
                    />

                    <!-- PNR -->
                    <x-input-field
                        name="pnr"
                        label="PNR"
                        wireModel="pnr"
                        placeholder="PNR"
                        containerClass="relative mt-1 col-span-3"
                        errorName="pnr"
                    />

                    <!-- المرجع -->
                    <x-input-field
                        name="reference"
                        label="الرقم المرجعي"
                        wireModel="reference"
                        placeholder="الرقم المرجعي"
                        containerClass="relative mt-1 col-span-3"
                        errorName="reference"
                    />

                    <!-- وسيلة الدفع -->
                    <x-select-field
                        wireModel="payment_type"
                        label="وسيلة الدفع"
                        :options="[
                            'creamy' => 'كريمي',
                            'kash' => 'كاش',
                            'visa' => 'فيزا'
                        ]"
                        placeholder=" وسيلة الدفع"
                        containerClass="relative mt-1 col-span-6"
                        errorName="payment_type"
                    />

                    <!-- رقم السند -->
                    <x-input-field
                        name="receipt_number"
                        label="رقم السند"
                        wireModel="receipt_number"
                        placeholder="رقم السند"
                        containerClass="relative mt-1 col-span-6"
                        errorName="receipt_number"
                    />
                </div>

                <!-- الصف الثالث -->
                <div class="grid md:grid-cols-4 gap-3">
                    <!-- العميل عبر -->
                    <x-select-field
                        wireModel="customer_via"
                        label="العميل عبر"
                        :options="[
                            'whatsapp' => 'واتساب',
                            'viber' => 'فايبر',
                            'instagram' => 'إنستغرام',
                            'other' => 'أخرى'
                        ]"
                        placeholder="العميل عبر  "
                        containerClass="relative mt-1"
                        errorName="customer_via"
                    />

                    <!-- المزود -->
                    <x-select-field
                        wireModel="provider_id"
                        label="المزود"
                        :options="$providers->pluck('name', 'id')->toArray()"
                        placeholder=" المزود"
                        containerClass="relative mt-1"
                        errorName="provider_id"
                    />

                    <!-- العميل -->
                    <x-select-field
                        wireModel="customer_id"
                        label="حساب العميل"
                        :options="$customers->pluck('name', 'id')->toArray()"
                        placeholder="حساب العميل"
                        containerClass="relative mt-1"
                        errorName="customer_id"
                    />

                    <!-- العمولة -->
                    @if($showCommission)
                    <x-input-field
                        name="commission"
                        label="العمولة"
                        wireModel="commission"
                        placeholder="العمولة"
                        type="number"
                        step="0.01"
                        containerClass="relative mt-1"
                    />
                    @endif
                </div>

                <!-- الصف الرابع -->
               <!-- الصف الرابع -->
<div class="grid grid-cols-24 gap-3 items-end">
    <!-- USD Buy -->
    <x-input-field
        name="usd_buy"
        label="مبلغ الشراء"
        wireModel="usd_buy"
        placeholder="مبلغ الشراء"
        type="number"
        step="0.01"
        wireChange="calculateProfit"
        containerClass="relative mt-1 col-span-3"
        fieldClass="w-full rounded-lg border border-gray-300 px-3 py-2 ... peer"
    />

    <!-- USD Sell -->
    <x-input-field
        name="usd_sell"
        label="مبلغ البيع"
        wireModel="usd_sell"
        placeholder="مبلغ البيع"
        type="number"
        step="0.01"
        wireChange="calculateProfit"
        containerClass="relative mt-1 col-span-3"
        fieldClass="w-full rounded-lg border border-gray-300 px-3 py-2 ... peer"
    />

    <!-- المبلغ المدفوع -->
    @if($payment_method !== 'all')
    <x-input-field
        name="amount_paid"
        label="المبلغ المدفوع"
        wireModel="amount_paid"
        placeholder="المبلغ المدفوع"
        type="number"
        step="0.01"
        wireChange="calculateDue"
        containerClass="relative mt-1 col-span-3"
        fieldClass="w-full rounded-lg border border-gray-300 px-3 py-2 ... peer"
    />
    @endif

    <!-- الربح -->
    <div class="col-span-3 flex items-end text-xs font-semibold text-[rgb(var(--primary-600))]">
        <div>
            <span>الربح:</span>
            <span>{{ number_format($sale_profit, 2) }}</span>
        </div>
    </div>

    <!-- المتبقي -->
    <div class="col-span-3 flex items-end text-xs font-semibold text-[rgb(var(--primary-600))]">
        <div>
            <span>المتبقي:</span>
            <span>{{ number_format($amount_due, 2) }}</span>
        </div>
    </div>

    <!-- الأزرار -->
    <div class="col-span-9 flex justify-end gap-3">
        <button wire:click="resetFields"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
            تنظيف الحقول
        </button>

        <button type="submit"
                class="text-white font-bold px-4 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
            تاكيد 
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