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
            <div class="{{ $containerClass }}">
            <input type="text" wire:model.live.debounce.500ms="search" class="{{ $fieldClass }}" placeholder="ابحث في جميع الحقول...">
                <label class="{{ $labelClass }}">بحث عام</label>
            </div>

            <!-- نوع الخدمة -->
<div class="{{ $containerClass }}">
    <select wire:model.live="serviceTypeFilter" class="{{ $fieldClass }}">
        <option value="">جميع أنواع الخدمات</option>
        @foreach($serviceTypes as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
        @endforeach
    </select>
    <label class="{{ $labelClass }}">نوع الخدمة</label>
</div>

<!-- المزود -->
<div class="{{ $containerClass }}">
    <select wire:model.live="providerFilter" class="{{ $fieldClass }}">
        <option value="">جميع المزودين</option>
        @foreach($providers as $provider)
            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
        @endforeach
    </select>
    <label class="{{ $labelClass }}">المزود</label>
</div>

<!-- الحساب -->
<div class="{{ $containerClass }}">
    <select wire:model.live="accountFilter" class="{{ $fieldClass }}">
        <option value="">جميع الحسابات</option>
        @foreach($accounts as $account)
            <option value="{{ $account->id }}">{{ $account->name }}</option>
        @endforeach
    </select>
    <label class="{{ $labelClass }}">الحساب</label>
</div>

<!-- من تاريخ -->
<div class="{{ $containerClass }}">
    <input type="date" wire:model.live="startDate" class="{{ $fieldClass }}" placeholder="من تاريخ">
    <label class="{{ $labelClass }}">من تاريخ</label>
</div>

<!-- إلى تاريخ -->
<div class="{{ $containerClass }}">
    <input type="date" wire:model.live="endDate" class="{{ $fieldClass }}" placeholder="إلى تاريخ">
    <label class="{{ $labelClass }}">إلى تاريخ</label>
</div>

<!-- PNR -->
<div class="{{ $containerClass }}">
    <input type="text" wire:model.live.debounce.500ms="pnrFilter" class="{{ $fieldClass }}" placeholder="بحث بـ PNR">
    <label class="{{ $labelClass }}">PNR</label>
</div>

<!-- المرجع -->
<div class="{{ $containerClass }}">
    <input type="text" wire:model.live.debounce.500ms="referenceFilter" class="{{ $fieldClass }}" placeholder="بحث بالمرجع">
    <label class="{{ $labelClass }}">المرجع</label>
</div>
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

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-2 py-1 cursor-pointer" wire:click="sortBy('created_at')">
                            التاريخ
                            @if($sortField === 'created_at')
                                @if($sortDirection === 'asc')
                                    ↑
                                @else
                                    ↓
                                @endif
                            @endif
                        </th>
                        <th class="px-2 py-1">اسم المستفيد</th>
                        <th class="px-2 py-1">نوع الخدمة</th>
                        <th class="px-2 py-1">المسار</th>
                        <th class="px-2 py-1">PNR</th>
                        <th class="px-2 py-1">المرجع</th>
                        <th class="px-2 py-1">الحدث</th>
                        <th class="px-2 py-1 cursor-pointer" wire:click="sortBy('usd_sell')">
                            سعر البيع
                            @if($sortField === 'usd_sell')
                                @if($sortDirection === 'asc')
                                    ↑
                                @else
                                    ↓
                                @endif
                            @endif
                        </th>
                        <th class="px-2 py-1 cursor-pointer" wire:click="sortBy('usd_buy')">
                            سعر الشراء
                            @if($sortField === 'usd_buy')
                                @if($sortDirection === 'asc')
                                    ↑
                                @else
                                    ↓
                                @endif
                            @endif
                        </th>
                        <th class="px-2 py-1">المزود</th>
                        <th class="px-2 py-1">الحساب</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach ($sales as $sale)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-1">{{ $sale->created_at->format('Y-m-d') }}</td>
                            <td class="px-2 py-1">{{ $sale->beneficiary_name ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->serviceType->name ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->route ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->pnr ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->reference ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->action ?? '-' }}</td>
                            <td class="px-2 py-1 font-bold" style="color: rgb(var(--primary-600));">
                                {{ number_format($sale->usd_sell, 2) }}
                            </td>
                            <td class="px-2 py-1 font-bold" style="color: rgb(var(--primary-500));">
                                {{ number_format($sale->usd_buy, 2) }}
                            </td>
                            <td class="px-2 py-1">{{ $sale->provider->name ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->account->name ?? '-' }}</td>
                           
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

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