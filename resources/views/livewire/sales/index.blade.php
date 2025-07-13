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
  <!-- حقل نوع الخدمة -->
<div class="{{ $containerClass }}">
    <select wire:model="service_item_id" wire:change="$refresh" class="{{ $fieldClass }}">
        <option value="">نوع الخدمة</option>
        @foreach($services as $service)
            <option value="{{ $service->id }}">{{ $service->label }}</option>
        @endforeach
    </select>
    <label class="{{ $labelClass }}">نوع الخدمة</label>
    @error('service_item_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
</div>

                    <!-- الحالة -->
                    <div class="{{ $containerClass }}">
                        <select wire:model="status" class="{{ $fieldClass }}">
                            <option value="">الحالة</option>
                            <option value="paid">مدفوع</option>
                            <option value="unpaid">غير مدفوع</option>
                        </select>
                        <label class="{{ $labelClass }}">الحالة</label>
                        @error('status') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
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
                    <div class="col-span-3 {{ $containerClass }}">
                        <input type="text" wire:model="beneficiary_name" class="{{ $fieldClass }}" placeholder="اسم المستفيد" />
                        <label class="{{ $labelClass }}">اسم المستفيد</label>
                    </div>

                    <!-- المسار -->
                    <div class="col-span-3 {{ $containerClass }}">
                        <input type="text" wire:model="route" class="{{ $fieldClass }}" placeholder="المسار / التفاصيل" />
                        <label class="{{ $labelClass }}">المسار / التفاصيل</label>
                    </div>

                    <!-- طريقة الدفع -->
                    <div class="col-span-3 {{ $containerClass }}">
                        <select wire:model="payment_method" class="{{ $fieldClass }}">
                            <option value="">اختر</option>
                            <option value="كاش">كاش</option>
                            <option value="حوالة">حوالة</option>
                        </select>
                        <label class="{{ $labelClass }}">طريقة الدفع</label>
                    </div>

                    <!-- اسم المودع -->
                    <div class="col-span-3 {{ $containerClass }}">
                        <input type="text" wire:model="depositor_name" class="{{ $fieldClass }}" placeholder="اسم المودع" />
                        <label class="{{ $labelClass }}">اسم المودع</label>
                    </div>
                </div>

                <div class="grid grid-cols-24 gap-3">
                    <!-- الرقم -->
                    <div class="col-span-2 {{ $containerClass }}">
                        <input type="text" wire:model="receipt_number" class="{{ $fieldClass }}" placeholder="رقم السند" />
                        <label class="{{ $labelClass }}">رقم السند</label>
                        @error('receipt_number') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- تاريخ البيع -->
                    <div class="col-span-4 {{ $containerClass }}">
                        <input type="date" wire:model="sale_date" class="{{ $fieldClass }}" placeholder="تاريخ البيع" />
                        <label class="{{ $labelClass }}">تاريخ البيع</label>
                        @error('sale_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- PNR -->
                    <div class="col-span-3 {{ $containerClass }}">
                        <input type="text" wire:model="pnr" class="{{ $fieldClass }}" placeholder="PNR" />
                        <label class="{{ $labelClass }}">PNR</label>
                        @error('pnr') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- المرجع -->
                    <div class="col-span-3 {{ $containerClass }}">
                        <input type="text" wire:model="reference" class="{{ $fieldClass }}" placeholder="المرجع" />
                        <label class="{{ $labelClass }}">المرجع</label>
                        @error('reference') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- وسيلة الدفع -->
                    <div class="col-span-6 {{ $containerClass }}">
                        <input type="text" wire:model="payment_type" class="{{ $fieldClass }}" placeholder="وسيلة الدفع" />
                        <label class="{{ $labelClass }}">وسيلة الدفع</label>
                        @error('payment_type') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- رقم الهاتف -->
                    <div class="col-span-6 {{ $containerClass }}">
                        <input type="text" wire:model="phone_number" class="{{ $fieldClass }}" placeholder="رقم الهاتف" />
                        <label class="{{ $labelClass }}">رقم الهاتف</label>
                        @error('phone_number') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- الصف الثالث -->
                <div class="grid md:grid-cols-4 gap-3">
                    <div class="{{ $containerClass }}">
                        <select wire:model="intermediary_id" class="{{ $fieldClass }}">
                            <option value="">العميل عبر</option>
                            @foreach($intermediaries as $i)
                                <option value="{{ $i->id }}">{{ $i->name }}</option>
                            @endforeach
                        </select>
                        <label class="{{ $labelClass }}">العميل عبر</label>
                        @error('intermediary_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
<!-- حقل المزود -->
<div class="{{ $containerClass }}">
    <select wire:model="provider_id" class="{{ $fieldClass }}">
        <option value="">اختر المزود</option>
        @foreach($providers as $provider)
            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
        @endforeach
    </select>
    <label class="{{ $labelClass }}">المزود</label>
    @error('provider_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
</div>


                    <div class="{{ $containerClass }}">
                        <select wire:model="customer_id" class="{{ $fieldClass }}">
                            <option value="">العميل</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <label class="{{ $labelClass }}">العميل</label>
                        @error('customer_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="{{ $containerClass }}">
                        <select wire:model="account_id" class="{{ $fieldClass }}">
                            <option value="">الحساب</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        <label class="{{ $labelClass }}">الحساب</label>
                        @error('account_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- الصف الرابع -->
                <div class="grid grid-cols-12 gap-3 items-end">
                    <!-- USD Buy -->
                    <div class="col-span-1 {{ $containerClass }}">
                        <input type="number" wire:model="usd_buy" wire:change="calculateProfit" step="0.01" class="{{ $fieldClass }} text-sm py-1 px-2" placeholder="USD Buy" />
                        <label class="{{ $labelClass }}">USD Buy</label>
                        @error('usd_buy') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- USD Sell -->
                    <div class="col-span-1 {{ $containerClass }}">
                        <input type="number" wire:model="usd_sell" wire:change="calculateProfit" step="0.01" class="{{ $fieldClass }} text-sm py-1 px-2" placeholder="USD Sell" />
                        <label class="{{ $labelClass }}">USD Sell</label>
                        @error('usd_sell') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- العمولة -->
                    <div class="col-span-1 {{ $containerClass }}">
                        <input type="number" wire:model="commission" step="0.01" class="{{ $fieldClass }} text-sm py-1 px-2" placeholder="العمولة" />
                        <label class="{{ $labelClass }}">العمولة</label>
                        @error('commission') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- الربح + المبلغ المدفوع + المتبقي -->
                    <div class="col-span-4 flex items-end gap-4">
                        <!-- الربح -->
                        <div class="text-xs font-semibold text-[rgb(var(--primary-600))]">
                            <span>الربح:</span>
                            <span>{{ number_format($sale_profit, 2) }}</span>
                        </div>

                        <!-- المبلغ المدفوع -->
                        <div class="relative w-full max-w-xs">
                            <input type="number" wire:model="amount_paid" wire:change="calculateDue"
                                class="{{ $fieldClass }} text-sm py-1 px-2" step="0.01" placeholder="المبلغ المدفوع" />
                            <label class="{{ $labelClass }}">المبلغ المدفوع</label>
                            @error('amount_paid') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

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

        <style>
            .peer:placeholder-shown + label {
                top: 0.75rem;
                font-size: 0.875rem;
                color: #6b7280;
            }
            
            .peer:not(:placeholder-shown) + label,
            .peer:focus + label {
                top: -0.5rem;
                font-size: 0.75rem;
                color: rgb(var(--primary-600));
            }

            select:required:invalid {
                color: #6b7280;
            }
            
            select option {
                color: #111827;
            }

            @media (max-width: 768px) {
                .flex-col.md\:flex-row > .md\:w-1\/2 {
                    width: 100% !important;
                }
            }

            /* تأثير hover للأزرار */
            button[style*="linear-gradient(to right, rgb(var(--primary-500))"]:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
            }

            button[style*="linear-gradient(to right, rgb(var(--primary-500))"]:active {
                transform: translateY(0);
            }
        </style>
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