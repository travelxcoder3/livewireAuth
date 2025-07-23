@php
use App\Tables\SalesTable;
$columns = SalesTable::columns();
@endphp

<div>
    <div>
       <!-- رسائل النجاح والخطأ -->
       @if($successMessage)
            <div x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 2000)"
                x-show="show"
                x-transition
                class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm z-50"
                style="background-color: rgb(var(--primary-500));">
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

   <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-12 gap-2 lg:gap-4 items-center">
    <!-- العنوان في اليسار -->
    <div class="col-span-12 md:col-span-4 lg:col-span-3 flex items-center justify-start">
        <h1 class="text-2xl font-bold text-[rgb(var(--primary-700))]">إدارة المبيعات</h1>
    </div>

    <!-- الكروت داخل كارد رئيسي في الوسط -->
    <div class="col-span-12 md:col-span-8 lg:col-span-6">
        <div class="bg-white border shadow rounded-xl px-2 py-2 flex flex-wrap justify-center gap-x-2 gap-y-2 items-center text-xs font-semibold text-gray-700 whitespace-nowrap">
            <!-- الربح -->
            <div class="flex flex-col items-center px-1 w-full sm:w-auto">
                <span class="text-[rgb(var(--primary-600))]">الربح</span>
                <span>{{ number_format($totalProfit, 2) }} {{ $currency }}</span>
            </div>
            <!-- العمولة المتوقعة (بناءً على جميع المبيعات) -->
<div class="flex flex-col items-center px-1 w-full sm:w-auto">
    <span class="text-[rgb(var(--primary-600))]">العمولة المتوقعة</span>
    <span>{{ number_format($userCommission, 2) }} {{ $currency }}</span>
</div>

<!-- العمولة المستحقة (بناءً على المبيعات المحصلة) -->
<div class="flex flex-col items-center px-1 w-full sm:w-auto">
    <span class="text-[rgb(var(--primary-600))]">العمولة المستحقة</span>
    <span>{{ number_format($userCommissionDue, 2) }} {{ $currency }}</span>
</div>
        </div>
    </div>

    <!-- كارد الإحصائيات المالية في اليمين -->
    <div class="col-span-12 md:col-span-4 lg:col-span-3">
        <div class="bg-white border shadow rounded-xl px-2 py-2 flex flex-wrap justify-center gap-x-2 gap-y-2 items-center text-xs font-semibold text-gray-700 whitespace-nowrap">
            <div class="flex flex-col items-center px-1 w-full sm:w-auto">
                <span class="text-[rgb(var(--primary-600))]">الإجمالي</span>
                <span>{{ number_format($totalAmount, 2) }} {{ $currency }}</span>
            </div>
            <div class="flex flex-col items-center px-1 w-full sm:w-auto">
                <span class="text-[rgb(var(--primary-600))]">المحصلة</span>
                <span>{{ number_format($totalReceived, 2) }} {{ $currency }}</span>
            </div>
            <div class="flex flex-col items-center px-1 w-full sm:w-auto">
                <span class="text-[rgb(var(--primary-600))]">الآجلة</span>
                <span>{{ number_format($totalPending, 2) }} {{ $currency }}</span>
            </div>
        </div>
    </div>
</div>


        <div class="space-y-6">
            <!-- الصف العلوي -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-2 lg:gap-4 items-end">
                <!-- نوع الخدمة + الحالة -->
                <div class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-2 gap-2 lg:gap-4 items-end">
                    <!-- نوع الخدمة -->
                        <x-select-field
                            wireModel="service_type_id"
                            label="نوع الخدمة"
                            :options="$services->pluck('label', 'id')->toArray()"
                            placeholder="اسم الخدمة"
                            fieldClass="peer rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm w-full sm:w-auto ..."
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
                <div class="lg:col-span-3 flex flex-col sm:flex-row justify-end gap-2 w-full">
                    @can('sales.reports.view')
                    <a href="{{ route('agency.sales.report.preview') }}"
                        class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm flex items-center justify-center w-full sm:w-auto"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        تقارير
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        @can('sales.create')
        <!-- نموذج الإضافة -->
        <div class="bg-white rounded-xl shadow-md p-4">
        <form wire:submit.prevent="save" class="space-y-4 text-sm" id="mainForm">
                <!-- السطر الأول -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-12 gap-2 lg:gap-3">
                
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

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-24 gap-2 lg:gap-3">
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
                    <x-date-picker
                        name="sale_date"
                        label="تاريخ البيع"
                        wireModel="sale_date"
                        placeholder="تاريخ البيع"
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
                <div class="grid md:grid-cols-4 gap-2 lg:gap-3">
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

                    <!-- المزود + تاريخ الخدمة -->
                    <div class="grid grid-cols-2 gap-3">
                        <!-- المزود -->
                        <x-select-field
                            wireModel="provider_id"
                            label="المزود"
                            :options="$providers->pluck('name', 'id')->toArray()"
                            placeholder=" المزود"
                            containerClass="relative mt-1"
                            errorName="provider_id"
                        />

                        <!-- تاريخ الخدمة -->
                        <x-date-picker
                            name="service_date"
                            label="تاريخ الخدمة"
                            wireModel="service_date"
                            placeholder="تاريخ الخدمة"
                            containerClass="relative mt-1"
                            errorName="service_date"
                        />
                    </div>

                    @if($showCustomerField)
                    <!-- العميل -->
                    <x-select-field
                        wireModel="customer_id"
                        label="حساب العميل"
                        :options="$customers->pluck('name', 'id')->toArray()"
                        placeholder="حساب العميل"
                        containerClass="relative mt-1"
                        errorName="customer_id"
                    />
                    @endif

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
<div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-24 gap-2 lg:gap-3 items-end">
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
    @php
        $showAmountPaid = $payment_method !== 'all';
    @endphp

    @if($showAmountPaid)
        <x-input-field
            name="amount_paid"
            label="المبلغ المدفوع"
            wireModel="amount_paid"
            placeholder="المبلغ المدفوع"
            type="number"
            step="0.01"
            wireChange="calculateDue"
            containerClass="relative mt-1 {{ $showExpectedDate ? 'col-span-3' : 'col-span-6' }}"
            fieldClass="w-full rounded-lg border border-gray-300 px-3 py-2 ... peer"
        />
    @endif

    <!-- تاريخ السداد المتوقع -->
    @if($showExpectedDate)
    <x-date-picker
        name="expected_payment_date"
        label="تاريخ السداد المتوقع"
        wireModel="expected_payment_date"
        placeholder="تاريخ السداد المتوقع"
        containerClass="relative mt-1 {{ $showAmountPaid ? 'col-span-3' : 'col-span-6' }}"
        errorName="expected_payment_date"
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
<div class="col-span-12 md:col-span-6 lg:col-span-6 flex items-end gap-2 lg:gap-3 w-full" style="margin-inline-start: auto;">
    <button type="button" wire:click="resetFields" 
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm flex items-center gap-2 w-full sm:w-auto">
        تنظيف الحقول
    </button>

    <button type="button" onclick="openFilterModal()"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-3 py-2 rounded-xl shadow transition duration-300 text-sm flex items-center w-full sm:w-auto">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
        </svg>
    </button>

        <!-- زر التحديث -->
    <button type="button" wire:click="resetFilters"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-3 py-2 rounded-xl shadow transition duration-300 text-sm flex items-center w-full sm:w-auto"
            title="إعادة تحميل الجدول كاملاً">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
        </svg>
    </button>

    <button type="submit"
            class="text-white font-bold px-4 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm w-full sm:w-auto"
            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        تاكيد 
    </button>
</div>
</div>

            </form>
        </div>
        @endcan

        
        <!-- جدول المبيعات -->
        <div class="overflow-x-auto">
        <x-data-table :rows="$sales" :columns="$columns" />
        </div>
<!-- نافذة الفلترة -->
<div id="filterModal" x-data="{ show: false }" x-show="show" @toggle-filter-modal.window="show = $event.detail"
     x-cloak class="fixed inset-0 z-50 bg-black/10 flex items-start justify-center pt-24 backdrop-blur-sm overflow-visible">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
        <button onclick="closeFilterModal()"
                class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
            &times;
        </button>

        <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
            فلترة النتائج
        </h3>
        
        <form id="filterForm" class="space-y-4">
            <!-- تاريخ البيع -->
            <div class="grid grid-cols-2 gap-4">
                <!-- في نافذة الفلترة -->
                <x-date-picker
                    name="start_date"
                    label="من تاريخ"
                    wireModel="filterInputs.start_date"
                    placeholder="من تاريخ"
                    containerClass="relative mt-1"
                />

                <x-date-picker
                    name="end_date"
                    label="إلى تاريخ"
                    wireModel="filterInputs.end_date"
                    placeholder="إلى تاريخ"
                    containerClass="relative mt-1"
                />

                <x-select-field
                    wireModel="filterInputs.service_type_id"
                    label="نوع الخدمة"
                    :options="$services->pluck('label', 'id')->toArray()"
                    placeholder="اختر الخدمة"
                    containerClass="relative mt-1"
                />

                <x-select-field
                    wireModel="filterInputs.status"
                    label="الحالة"
                    :options="[
                        '' => 'الكل',
                        'issued' => 'تم الإصدار',
                        'refunded' => 'تم الاسترداد',
                        'canceled' => 'تم الإلغاء',
                        'pending' => 'قيد الانتظار',
                        'reissued' => 'إعادة الإصدار',
                        'void' => 'لاغية',
                        'paid' => 'مدفوعة',
                        'unpaid' => 'غير مدفوعة'
                    ]"
                    placeholder="الحالة"
                    containerClass="relative mt-1"
                />

                <x-select-field
                    wireModel="filterInputs.customer_id"
                    label="العميل"
                    :options="$customers->pluck('name', 'id')->toArray()"
                    placeholder="اختر العميل"
                    containerClass="relative mt-1"
                />

                <x-select-field
                    wireModel="filterInputs.provider_id"
                    label="المزود"
                    :options="$providers->pluck('name', 'id')->toArray()"
                    placeholder="اختر المزود"
                    containerClass="relative mt-1"
                />

                <x-date-picker
                    name="service_date"
                    label="تاريخ الخدمة"
                    wireModel="filterInputs.service_date"
                    placeholder="تاريخ الخدمة"
                    containerClass="relative mt-1"
                />

                <x-select-field
                    wireModel="filterInputs.customer_via"
                    label="العميل عبر"
                    :options="[
                        '' => 'الكل',
                        'whatsapp' => 'واتساب',
                        'viber' => 'فايبر',
                        'instagram' => 'إنستغرام',
                        'other' => 'أخرى'
                    ]"
                    placeholder="العميل عبر"
                    containerClass="relative mt-1"
                />

                <x-input-field
                    name="route"
                    label="Route"
                    wireModel="filterInputs.route"
                    placeholder="Route"
                    containerClass="relative mt-1"
                />

                <x-select-field
                    wireModel="filterInputs.payment_method"
                    label="طريقة الدفع"
                    :options="[
                        '' => 'الكل',
                        'kash' => 'كاش',
                        'part' => 'جزئي',
                        'all' => 'كامل جزئي'
                    ]"
                    placeholder="طريقة الدفع"
                    containerClass="relative mt-1"
                />

                <x-select-field
                    wireModel="filterInputs.payment_type"
                    label="وسيلة الدفع"
                    :options="[
                        '' => 'الكل',
                        'creamy' => 'كريمي',
                        'kash' => 'كاش',
                        'visa' => 'فيزا'
                    ]"
                    placeholder="وسيلة الدفع"
                    containerClass="relative mt-1"
                />
<div class="mt-6 flex justify-center gap-3">
<button type="button" onclick="closeFilterModal()"
    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm w-full sm:w-auto">
    إلغاء
</button>

            <button type="button" wire:click="applyFilters"
                    class="text-white font-bold px-4 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm w-full sm:w-auto"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                تطبيق الفلترة
            </button>
            </div>
        </form>
    </div>
</div>
    
<!-- نافذة المعاينة الكبيرة -->
<div id="previewModal" class="fixed inset-0 z-50 bg-black/20 flex items-center justify-center hidden backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 p-8 relative flex flex-col min-h-[60vh]">
        <button onclick="closePreviewModal()"
                class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-2xl font-bold">
            &times;
        </button>
        <h2 class="text-2xl font-bold mb-6 text-center text-[rgb(var(--primary-700))]">معاينة التقرير</h2>
        <div class="flex-1 overflow-auto mb-6">
            <!-- عرض نفس جدول المبيعات -->
            <x-data-table :rows="$sales" :columns="$columns" />
        </div>
        <div class="flex justify-center gap-6 mt-4">
            <button type="button" onclick="openReportModal('pdf')"
                class="text-white font-bold px-8 py-3 rounded-xl shadow-md transition duration-300 text-lg"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                تقرير PDF
            </button>
            <button type="button" onclick="openReportModal('excel')"
                class="text-white font-bold px-8 py-3 rounded-xl shadow-md transition duration-300 text-lg"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                تقرير Excel
            </button>
        </div>
    </div>
</div>

<script>
    let currentReportType = '';
function openFilterModal() {
    window.dispatchEvent(new CustomEvent('toggle-filter-modal', { detail: true }));
}
function closeFilterModal() {
    window.dispatchEvent(new CustomEvent('toggle-filter-modal', { detail: false }));
}

function applyFilters() {
    Livewire.emit('applyFilters');
    closeFilterModal();
}
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
        document.addEventListener('livewire:load', () => {
        Livewire.on('filters-applied', () => {
            window.dispatchEvent(new CustomEvent('toggle-filter-modal', { detail: false }));
        });
    });
// أضف هذا الكود في قسم الـ script
document.addEventListener('livewire:init', () => {
    Livewire.on('filters-applied', () => {
        closeFilterModal();
    });
});

function openPreviewModal() {
    document.getElementById('previewModal').classList.remove('hidden');
}
function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}
</script>