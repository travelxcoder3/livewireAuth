@php
use App\Services\ThemeService;
$themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
$colors = ThemeService::getCurrentThemeColors($themeName);

$fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
$labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
$containerClass = 'relative mt-1';
@endphp


<div class="space-y-6">


   <div class="flex justify-between items-center">
    <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
        إدارة الحسابات
    </h2>
<div class="flex items-center gap-2">
    <label class="text-sm font-semibold text-gray-700">الإجمالي:</label>
    <input 
        type="text" 
        value="{{ number_format($totalSales, 2) }}" 
        readonly 
        class="bg-gray-100 border border-gray-300 rounded px-3 py-1 text-sm text-gray-700 w-32 text-center"
    >
</div>

</div>

    <!-- فلاتر البحث -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4">
           <x-input-field
                name="search"
                label="بحث عام"
                wireModel="search"
                placeholder="ابحث في جميع الحقول..."
                containerClass="relative"
                fieldClass="{{ $fieldClass }}"
            />

            <!-- نوع الخدمة -->
            <x-select-field
                label="نوع الخدمة"
                name="service_type"
                wireModel="serviceTypeFilter"
                :options="$serviceTypes->pluck('label', 'id')->toArray()"
                placeholder="جميع أنواع الخدمات"
                containerClass="relative"
            />

<!-- المزود -->
            <x-select-field
                label="المزود"
                name="provider"
                wireModel="providerFilter"
                :options="$providers->pluck('name', 'id')->toArray()"
                placeholder="جميع المزودين"
                containerClass="relative"
            />



<!-- الحساب -->
            <x-select-field
                label="الحساب"
                name="account"
                wireModel="accountFilter"
                :options="$accounts->pluck('name', 'id')->toArray()"
                placeholder="جميع الحسابات"
                containerClass="relative"
            />


<!-- من تاريخ -->
            <x-input-field
                name="start_date"
                label="من تاريخ"
                wireModel="startDate"
                type="date"
                containerClass="relative"
                fieldClass="{{ $fieldClass }}"
            />

<!-- إلى تاريخ -->
            <x-input-field
                name="end_date"
                label="إلى تاريخ"
                wireModel="endDate"
                type="date"
                containerClass="relative"
                fieldClass="{{ $fieldClass }}"
            />

<!-- PNR -->
            <x-input-field
                name="pnr"
                label="PNR"
                wireModel="pnrFilter"
                placeholder="بحث بـ PNR"
                containerClass="relative"
                fieldClass="{{ $fieldClass }}"
            />

<!-- المرجع -->
            <x-input-field
                name="reference"
                label="المرجع"
                wireModel="referenceFilter"
                placeholder="بحث بالمرجع"
                containerClass="relative"
                fieldClass="{{ $fieldClass }}"
            />
        </div>

<div class="flex flex-col md:flex-row justify-end items-center gap-2 mt-3">

    <!-- زر إعادة تعيين الفلاتر -->
    <button wire:click="resetFilters"
        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
        إعادة تعيين الفلاتر
    </button>

    <!-- زر تصدير إكسل -->
    @can('accounts.export')
    <button onclick="openReportModal('excel')"
        class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        تصدير إكسل
    </button>
    @endcan

    <!-- زر طباعة PDF -->
    @can('accounts.print')
    <button onclick="openReportModal('pdf')"
        class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        طباعة PDF
    </button>
    @endcan

    <!-- زر إصدار فاتورة -->
    @can('accounts.invoice')
    <button onclick="openInvoiceModal()"
        class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        إصدار فاتورة
    </button>
    @endcan

</div>


    <!-- رسالة لا توجد نتائج -->
    @if($sales->isEmpty())
        <div class="text-center text-gray-400 py-6">
            لا توجد نتائج مطابقة للفلاتر المحددة
        </div>
    @endif

    <!-- جدول العمليات -->
     <div class="h-6"></div>

    @php
    use App\Tables\AccountTable;
    $columns = AccountTable::columns();
@endphp
<x-data-table :rows="$sales" :columns="$columns" />

    <!-- Pagination -->
        @if($sales->hasPages())
            <div class="px-4 py-2 border-t border-gray-200">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

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

    <!-- تأكيد الحذف -->
    {{-- @include('livewire.confirmation-modal') --}}
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
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            
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
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            
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
                    'amount_received' => 'المبلغ',
                    'account' => 'الحساب',
                    'reference' => 'المرجع',
                    'pnr' => 'PNR',
                    'route' => 'Route',
                    'action' => 'الإجراء',
                    'user' => 'اسم الموظف'
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
            window.open(`/agency/accounts/report/pdf?start_date=${startDate}&end_date=${endDate}&service_type=${serviceTypeFilter}&provider=${providerFilter}&account=${accountFilter}&pnr=${pnrFilter}&reference=${referenceFilter}`, '_blank');
        } else {
            window.open(`/agency/accounts/report/excel?start_date=${startDate}&end_date=${endDate}&service_type=${serviceTypeFilter}&provider=${providerFilter}&account=${accountFilter}&pnr=${pnrFilter}&reference=${referenceFilter}`, '_blank');
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