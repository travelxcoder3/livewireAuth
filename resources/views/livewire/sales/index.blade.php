<div>
<div class="space-y-6">
    <!-- العنوان الرئيسي -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
    <!-- العنوان -->
    <h2 class="text-2xl font-bold"
        style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
        إدارة المبيعات
    </h2>

   <!-- كارد الإحصائيات -->
    <div class="bg-white rounded-xl shadow-md border px-6 py-4 flex flex-wrap gap-6 items-center text-sm font-bold text-gray-700 w-full lg:w-auto">
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
            <span class="text-[rgb(var(--primary-600))]">الربح:</span>
            <span>{{ number_format($totalProfit, 2) }} {{ $currency }}</span>
        </div>
    </div>


    <!-- أزرار التحكم -->
    <div class="flex gap-2 flex-wrap">
        <button wire:click="resetFields"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
            تنظيف الحقول
        </button>
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


    @can('sales.create')
    <!-- نموذج الإضافة -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <form wire:submit.prevent="save" class="space-y-4 text-sm" id="mainForm">
            @php
                $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                $containerClass = 'relative mt-1';
            @endphp

            <!-- الصف الأول -->
            <div class="grid md:grid-cols-4 gap-3">
                <div class="{{ $containerClass }}">
                    <input type="text" wire:model="beneficiary_name" class="{{ $fieldClass }}" placeholder="اسم المستفيد" />
                    <label class="{{ $labelClass }}">اسم المستفيد</label>
                    @error('beneficiary_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="{{ $containerClass }}">
                    <input type="date" wire:model="sale_date" class="{{ $fieldClass }}" placeholder="تاريخ البيع" />
                    <label class="{{ $labelClass }}">تاريخ البيع</label>
                    @error('sale_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="{{ $containerClass }}">
                    <select wire:model="service_type_id" class="{{ $fieldClass }}">
                        <option value="">نوع الخدمة</option>
                        @foreach($serviceTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <label class="{{ $labelClass }}">نوع الخدمة</label>
                    @error('service_type_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="{{ $containerClass }}">
                    <select wire:model="provider_id" class="{{ $fieldClass }}">
                        <option value="">المزود</option>
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <label class="{{ $labelClass }}">المزود</label>
                    @error('provider_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- الصف الثاني -->
            <div class="grid md:grid-cols-4 gap-3">
                <div class="{{ $containerClass }}">
                    <select wire:model="intermediary_id" class="{{ $fieldClass }}">
                        <option value="">الوسيط</option>
                        @foreach($intermediaries as $i)
                            <option value="{{ $i->id }}">{{ $i->name }}</option>
                        @endforeach
                    </select>
                    <label class="{{ $labelClass }}">الوسيط</label>
                    @error('intermediary_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
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

                <div class="{{ $containerClass }}">
                    <input type="text" wire:model="action" class="{{ $fieldClass }}" placeholder="الإجراء" />
                    <label class="{{ $labelClass }}">الإجراء</label>
                    @error('action') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- الصف الثالث -->
         <div class="grid md:grid-cols-4 gap-3">
            <div class="{{ $containerClass }}">
                <input type="number" wire:model="usd_buy" wire:change="calculateProfit" step="0.01" class="{{ $fieldClass }}" placeholder="USD Buy" />
                <label class="{{ $labelClass }}">USD Buy</label>
                @error('usd_buy') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="{{ $containerClass }}">
              <input type="number" wire:model="usd_sell" wire:change="calculateProfit" step="0.01" class="{{ $fieldClass }}" placeholder="USD Sell" />

                <label class="{{ $labelClass }}">USD Sell</label>
                @error('usd_sell') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="{{ $containerClass }}">
                <input type="number" wire:model="sale_profit" step="0.01" class="{{ $fieldClass }}" placeholder="الربح" readonly />
                <label class="{{ $labelClass }}">الربح</label>
                @error('sale_profit') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

                <div class="flex items-center gap-2">
                    <div class="flex-1 relative">
                    <input type="number" wire:model="amount_received" wire:change="calculateDue"
                       class="{{ $fieldClass }}" step="0.01" placeholder="المبلغ المدفوع" />
                        <label class="{{ $labelClass }}">المبلغ المدفوع</label>
                        @error('amount_received') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="text-xs mt-1 font-semibold"
                style="color: rgb(var(--primary-600));">
                <span>المتبقي:</span>
                <span>{{ number_format($amount_due, 2) }}</span>
            </div>
        </div>




</div>

            </div>

            <!-- الصف الرابع -->
            <div class="grid md:grid-cols-4 gap-3">
                <div class="{{ $containerClass }}">
                    <input type="text" wire:model="reference" class="{{ $fieldClass }}" placeholder="المرجع" />
                    <label class="{{ $labelClass }}">المرجع</label>
                    @error('reference') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="{{ $containerClass }}">
                    <input type="text" wire:model="pnr" class="{{ $fieldClass }}" placeholder="PNR" />
                    <label class="{{ $labelClass }}">PNR</label>
                    @error('pnr') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="{{ $containerClass }}">
                    <input type="text" wire:model="route" class="{{ $fieldClass }}" placeholder="Route" />
                    <label class="{{ $labelClass }}">Route</label>
                    @error('route') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="{{ $containerClass }}">
                    <input type="text" wire:model="depositor_name" class="{{ $fieldClass }}" placeholder="اسم المودع" />
                    <label class="{{ $labelClass }}">اسم المودع</label>
                    @error('depositor_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- صف الملاحظات والأزرار -->
            <div class="flex flex-col md:flex-row gap-4 pt-4">
                <!-- حقل الملاحظات (50% من العرض) -->
                <div class="{{ $containerClass }} md:w-1/2">
                    <textarea wire:model="note" rows="2" class="{{ $fieldClass }}" placeholder="ملاحظات"></textarea>
                    <label class="{{ $labelClass }}">ملاحظات</label>
                    @error('note') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- الأزرار (50% من العرض) -->
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 md:w-1/2 items-center justify-center">
                    <button type="submit"
                        class="flex-1 text-white font-bold px-4 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm w-full"
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

    <!-- جدول المبيعات -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-2 py-1">التاريخ</th>
                        <th class="px-2 py-1">المستفيد</th>
                        <th class="px-2 py-1">العميل</th>
                        <th class="px-2 py-1">الخدمة</th>
                        <th class="px-2 py-1">المزود</th>
                        <th class="px-2 py-1">الوسيط</th>
                        <th class="px-2 py-1">Buy</th>
                        <th class="px-2 py-1">Sell</th>
                        <th class="px-2 py-1">الربح</th>
                        <th class="px-2 py-1">المبلغ</th>
                        <th class="px-2 py-1">الحساب</th>
                        <th class="px-2 py-1">المرجع</th>
                        <th class="px-2 py-1">PNR</th>
                        <th class="px-2 py-1">Route</th>
                        <th class="px-2 py-1">الإجراء</th>
                        <th class="px-2 py-1">الموظف</th>
                        <th class="px-2 py-1">خيارات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse ($sales as $sale)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-1 whitespace-nowrap">{{ $sale->sale_date }}</td>
                            <td class="px-2 py-1">{{ $sale->beneficiary_name }}</td>
                            <td class="px-2 py-1">{{ $sale->customer->name ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->serviceType->name ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->provider->name ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->intermediary->name ?? '-' }}</td>
                            <td class="px-2 py-1" style="color: rgb(var(--primary-500)); font-weight: 600;">{{ number_format($sale->usd_buy, 2) }}</td>
                            <td class="px-2 py-1" style="color: rgb(var(--primary-600)); font-weight: 600;">{{ number_format($sale->usd_sell, 2) }}</td>
                            <td class="px-2 py-1" style="color: rgb(var(--primary-700)); font-weight: 600;">{{ number_format($sale->sale_profit, 2) }}</td>
                            <td class="px-2 py-1">{{ number_format($sale->amount_received, 2) }}</td>
                            <td class="px-2 py-1">{{ $sale->account->name ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $sale->reference }}</td>
                            <td class="px-2 py-1">{{ $sale->pnr }}</td>
                            <td class="px-2 py-1">{{ $sale->route }}</td>
                            <td class="px-2 py-1">{{ $sale->action }}</td>
                            <td class="px-2 py-1">{{ $sale->user->name ?? '-' }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <button wire:click="duplicate({{ $sale->id }})"
                                    class="font-medium text-xs mx-1" style="color: rgb(var(--primary-600)); hover:color: rgb(var(--primary-800));">تكرار</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="text-center py-4 text-gray-400">لا توجد عمليات بيع</td>
                        </tr>
                    @endforelse
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
</div>