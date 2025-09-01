<?php $currency = Auth::user()->agency->currency ?? 'USD'; ?>

<div class="space-y-6" x-data="{ sel: @entangle('selectedGroups') }">
    <x-toast :message="$toastMessage" :type="$toastType ?? 'success'" />
    <div class="flex justify-between items-center mb-1">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: .5rem;">
            فاتورة المزوّد   : {{ $provider->name }}
        </h2>

        <div class="flex items-center gap-2">
            <label class="text-xs sm:text-sm font-semibold text-gray-700">الإجمالي:</label>
            <?php $totalBuy = collect($groups ?? [])->sum('net_cost'); ?>
            <input type="text" value="{{ number_format($totalBuy, 2) }}" readonly
                   class="bg-gray-100 border border-gray-300 rounded px-3 py-1 text-sm text-gray-700 w-32 text-center">

            <?php if (count($groups ?? []) > 0) { ?>
                <x-primary-button
                    type="button"
                    wire:click="openBulkInvoiceModal"
                    :loading="true"
                    target="openBulkInvoiceModal"
                    busyText="جارٍ الفتح…"
                    delay="shortest"
                    class="px-3 py-2 text-sm rounded-lg"
                >
                    إصدار فاتورة مجمعة
                </x-primary-button>

            <?php } ?>


            <a href="{{ route('agency.provider-detailed-invoices') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg border transition text-sm font-medium
                      bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))] hover:shadow-md hover:text-[rgb(var(--primary-700))]">
                <svg class="h-5 w-5 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>رجوع</span>
            </a>
        </div>
    </div>

    <!-- فلاتر -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4 items-end">
            <x-input-field name="beneficiary_search" label="بحث باسم المستفيد" wireModel="search" placeholder="مثال: أمير علي"/>
            <x-date-picker name="fromDate" label="من تاريخ" wireModel="fromDate" />
            <x-date-picker name="toDate"   label="إلى تاريخ" wireModel="toDate" />
            <div class="mt-3 flex justify-end">
                <button type="button" wire:click="resetFilters"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
                    إعادة تعيين الفلاتر
                </button>
            </div>
        </div>
    </div>

    <!-- جدول المجموعات -->
    <div
        x-data="{ groupsSel: @entangle('selectedGroups') }"
        x-init="
            const h = $refs.selectAll;
            const sync = () => {
                const len  = Array.isArray(groupsSel) ? groupsSel.length : (groupsSel?.length ?? 0);
                const rows = {{ count($groups ?? []) }};
                h.checked       = (len > 0 && len === rows);
                h.indeterminate = (len > 0 && len < rows);
            };
            sync();
            $watch('groupsSel', sync);
        "
        class="bg-white rounded-xl shadow-md overflow-hidden"
    >
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
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
                        <th class="px-2 py-1">تكلفة الخدمة (أصل)</th>
                        <th class="px-2 py-1">الاستردادات</th>
                        <th class="px-2 py-1">صافي مستحق للمزوّد</th>
                        <th class="px-2 py-1">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (!empty($groups)) { ?>
                        <?php foreach ($groups as $index => $g) { ?>
                            <?php $isCredit = ($g->net_cost ?? 0) < 0; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1 text-center">
                                    <input type="checkbox"
                                           wire:key="cb-{{ (string)$g->group_key }}"
                                           wire:model.live="selectedGroups"
                                           value="{{ (string)$g->group_key }}"
                                           class="rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]"
                                           style="accent-color: rgb(var(--primary-500));">
                                </td>
                                <td class="px-2 py-1">{{ $g->beneficiary_name }}</td>
                                <td class="px-2 py-1">{{ $g->sale_date }}</td>
                                <td class="px-2 py-1">{{ $g->service_label }}</td>
                                <td class="px-2 py-1 text-blue-700">{{ number_format($g->cost_total_true, 2) }} {{ $currency }}</td>
                                <td class="px-2 py-1 text-rose-700">{{ number_format($g->refund_total, 2) }} {{ $currency }}</td>
                                <td class="px-2 py-1 text-emerald-700 font-semibold">{{ number_format($g->net_cost, 2) }} {{ $currency }}</td>
                                <td class="px-2 py-1">
                                    <button wire:click="showDetails(<?php echo $index; ?>)"
                                            class="text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold mr-3">
                                        تفاصيل
                                    </button>
                                    <span class="mx-1 text-gray-300">|</span>
                                    <button wire:click="openInvoiceModal('{{ (string)$g->group_key }}')"
                                            class="font-semibold <?php echo $isCredit ? 'text-red-500' : 'text-[rgb(var(--primary-600))]'; ?> hover:text-black">
                                        <?php echo $isCredit ? 'إشعار خصم' : 'فاتورة فردية'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8" class="text-center text-gray-400 py-6">لا توجد نتائج مطابقة.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- مودال التفاصيل -->
    <?php if (!empty($activeRow)) { ?>
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-24 backdrop-blur-sm bg-black/30">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6 relative max-h-[80vh] overflow-y-auto">
                <button wire:click="closeModal" class="absolute top-2 left-3 text-gray-500 hover:text-red-600 text-lg">&times;</button>
                <h3 class="text-lg font-bold mb-4 text-[rgb(var(--primary-700))] border-b pb-2">
                    تفاصيل التكلفة للمستفيد: {{ $activeRow->beneficiary_name }}
                </h3>
                <div class="mb-6 overflow-x-auto">
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="px-3 py-2">التاريخ</th>
                                <th class="px-3 py-2">التكلفة</th>
                                <th class="px-3 py-2">الحالة</th>
                                <th class="px-3 py-2">المرجع</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach (($activeRow->scenarios ?? []) as $s) { ?>
                                <tr>
                                    <td class="px-3 py-2">{{ $s['date'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-blue-700">{{ number_format($s['usd_buy'] ?? 0, 2) }} {{ $currency }}</td>
                                    <td class="px-3 py-2">{{ $s['status'] ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $s['note'] ?? '-' }}</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><strong>أصل التكلفة:</strong> {{ number_format($activeRow->cost_total_true, 2) }} {{ $currency }}</div>
                    <div><strong>الاستردادات:</strong> {{ number_format($activeRow->refund_total, 2) }} {{ $currency }}</div>
                    <div class="col-span-2 font-semibold"><strong>صافي مستحق للمزوّد:</strong> {{ number_format($activeRow->net_cost, 2) }} {{ $currency }}</div>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- مودال الفاتورة الفردية/إشعار الخصم -->
    <?php if (!empty($showInvoiceModal) && !empty($currentGroupKey)) { ?>
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-24 backdrop-blur-sm bg-black/40" x-cloak>
            <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 relative">
                <button wire:click="$set('showInvoiceModal', false)"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>

                <h2 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    {{ $isCreditNote ? 'إشعار خصم للمزوّد' : 'فاتورة المزوّد' }}
                </h2>


                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">
                            الضريبة
                            <span class="text-gray-500 text-xs">({{ $taxIsPercent ? '%' : $currency }})</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="number" step="0.01" min="0" wire:model.lazy="taxAmount"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <select wire:model="taxIsPercent" class="rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                <option value="1">%</option>
                                <option value="0">{{ $currency }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-3 text-sm">
                        <div class="flex justify-between"><span>Subtotal:</span><span>{{ number_format($invoiceTotals['base'],2) }} {{ $currency }}</span></div>
                        <div class="flex justify-between"><span>Tax:</span><span>{{ number_format($invoiceTotals['tax'],2) }} {{ $currency }}</span></div>
                        <div class="flex justify-between font-semibold"><span>Grand Total:</span><span>{{ number_format($invoiceTotals['net'],2) }} {{ $currency }}</span></div>
                    </div>

                    <div class="flex justify-end gap-2 mt-2">
                        <x-primary-button
    type="button"
    x-data
    @click="window.dispatchEvent(new CustomEvent('confirm:open',{detail:{
        title:'تأكيد حفظ الضريبة',
        message:'سيتم حفظ/تحديث ضريبة الفاتورة لهذه المجموعة. متابعة؟',
        icon:'info',
        confirmText:'حفظ',
        cancelText:'إلغاء',
        onConfirm:'addTaxForGroup',
        payload:null
    }}))"
    wire:loading.attr="disabled"
>
    حفظ/تحديث
</x-primary-button>

                            @if($currentInvoiceId)
                                <x-primary-button
    type="button"
    :loading="true"
    target="downloadSingleInvoicePdf"
    busyText="جاري إنشاء الملف…"
    delay="longest"
    x-data
    @click="window.dispatchEvent(new CustomEvent('confirm:open',{detail:{
        title:'تأكيد تحميل PDF',
        message:'سيتم توليد ملف PDF وبدء التحميل لهذه الفاتورة. متابعة؟',
        icon:'info',
        confirmText:'تحميل',
        cancelText:'إلغاء',
        onConfirm:'downloadSingleInvoicePdf',
        payload:null   // اجعل دالة PHP تقبل معاملًا اختياريًا = null إن كانت بلا معامل
    }}))"
>
    تحميل PDF
</x-primary-button>

                            @endif
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

        <!-- مودال الفاتورة المجمعة -->
        <?php if (!empty($showBulkInvoiceModal)) { ?>
            <div class="fixed inset-0 z-50 flex items-start justify-center pt-24 backdrop-blur-sm bg-black/40" x-cloak>
                <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 relative">

                <button wire:click="$set('showBulkInvoiceModal', false)"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>

                <h2 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    إصدار فاتورة مجمعة
                </h2>

                <form wire:submit.prevent="createBulkInvoice" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">اسم الجهة/المزوّد</label>
                        <input type="text" wire:model.defer="invoiceEntityName"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">تاريخ الفاتورة</label>
                        <input type="date" wire:model.defer="invoiceDate"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">
                            الضريبة (مجمّعة)
                            <span class="text-gray-500 text-xs">({{ $bulkTaxIsPercent ? '%' : $currency }})</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="number" step="0.01" min="0" wire:model.lazy="bulkTaxAmount"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <select wire:model="bulkTaxIsPercent" class="rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                <option value="1">%</option>
                                <option value="0">{{ $currency }}</option>
                            </select>
                        </div>
                    </div>

                    <?php
                        $__bulkTax  = !empty($bulkTaxIsPercent) ? round(($bulkSubtotal ?? 0) * (($bulkTaxAmount ?? 0)/100), 2) : (float)($bulkTaxAmount ?? 0);
                        $__bulkGrand = (float)($bulkSubtotal ?? 0) + (float)$__bulkTax;
                    ?>
                    <div class="bg-gray-50 rounded-lg p-3 text-sm">
                        <div class="flex justify-between"><span>Subtotal:</span><span>{{ number_format($bulkSubtotal ?? 0,2) }} {{ $currency }}</span></div>
                        <div class="flex justify-between"><span>Tax:</span><span>{{ number_format($__bulkTax,2) }} {{ $currency }}</span></div>
                        <div class="flex justify-between font-semibold"><span>Grand Total:</span><span>{{ number_format($__bulkGrand,2) }} {{ $currency }}</span></div>
                    </div>

                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" wire:click="$set('showBulkInvoiceModal', false)"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
                            إلغاء
                        </button>
                            <x-primary-button
    type="button"
    :loading="true"
    target="createBulkInvoice"
    busyText="جاري إنشاء الفاتورة…"
    delay="longest"
    x-data
    @click="window.dispatchEvent(new CustomEvent('confirm:open',{detail:{
        title:'تأكيد إنشاء فاتورة مجمّعة',
        message:'سيتم إنشاء فاتورة مجمّعة للعمليات المحددة. متابعة؟',
        icon:'check',
        confirmText:'إنشاء',
        cancelText:'إلغاء',
        onConfirm:'createBulkInvoice',
        payload:null
    }}))"
>
    تأكيد وإنشاء
</x-primary-button>

                    </div>
                </form>
            </div>
        </div>
    <?php } ?>
    <x-confirm-dialog />
</div>
