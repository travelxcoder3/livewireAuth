@php
    $accountNumber =
        \App\Models\Customer::where('agency_id', $customer->agency_id)
            ->orderBy('created_at')
            ->pluck('id')
            ->search($customer->id) + 1;
    $currency = Auth::user()->agency->currency ?? 'USD';
    // حساب إجمالي المبيعات الفعالة (باستثناء الملغاة)
    $activeSales = $sales->whereNotIn('status', ['Void'])->sum('usd_sell');

    // حساب إجمالي التحصيلات (من التحصيلات المدفوعة من العميل)
    $directPayments = $sales->sum('amount_paid');
    // حساب الرصيد الفارق بناءً على إجمالي المبيعات - إجمالي التحصيلات
    $netBalance = $activeSales - $directPayments;
@endphp
<div class="space-y-6">

    <!-- 🔵 العنوان العلوي -->
    <div class="flex justify-between items-center mb-4">
        <!-- عنوان الصفحة -->
        <h2
            class="text-2xl font-bold text-[rgb(var(--primary-700))] border-b border-[rgba(var(--primary-200),0.5)] pb-2">
            التقرير المالي لمشتريات العميل
        </h2>

        <!-- الزرين: التصدير + الرجوع -->
        <div class="flex gap-2">
            <!-- زر التصدير -->
            <a href="{{ route('agency.reports.customer-accounts.pdf', $customer->id) }}" target="_blank"
                class="flex items-center gap-2 text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                <i class="fas fa-file-export"></i>
                <span>تصدير التقرير</span>
            </a>

            <!-- زر الرجوع -->
            <button onclick="history.back();"
                class="group flex items-center gap-2 text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                <i class="fas fa-arrow-left transform transition-transform duration-300 group-hover:-translate-x-1"></i>
                <span>رجوع</span>
            </button>
        </div>
    </div>


    <!-- 🔷 بيانات العميل -->
    <div>
        <h3 class="text-lg font-semibold text-[rgb(var(--primary-600))] mb-2 px-4">بيانات العميل</h3>
        <div class="bg-white rounded-xl shadow-md p-4 grid md:grid-cols-3 gap-4 text-sm text-center">
            <div><span class="text-gray-500">اسم العميل:</span> <strong>{{ $customer->name }}</strong></div>
            <div><span class="text-gray-500">نوع الحساب:</span>
                {{ match ($customer->account_type) {
                    'individual' => 'فرد',
                    'company' => 'شركة',
                    'organization' => 'منظمة',
                    default => 'غير محدد',
                } }}
            </div>
            <div><span class="text-gray-500">رقم الحساب:</span> {{ $accountNumber }}</div>
            <div><span class="text-gray-500">تاريخ فتح الحساب:</span> {{ $customer->created_at->format('Y-m-d') }}</div>
            <div><span class="text-gray-500">الجوال:</span> {{ $customer->phone ?? '-' }}</div>
            <div><span class="text-gray-500">البريد الإلكتروني:</span> {{ $customer->email ?? '-' }}</div>
            <div><span class="text-gray-500">العملة:</span> {{ Auth::user()?->agency?->currency ?? 'USD' }}</div>
        </div>
    </div>
    <!-- 🟦 العمليات -->
    <div>
        <h3 class="text-lg font-semibold text-[rgb(var(--primary-600))] mb-2">عمليات البيع والتحصيل</h3>
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <table class="w-full text-sm border text-center">
                <thead class="bg-[rgb(var(--primary-500))] text-white text-sm">
                    <tr>
                        <th class="p-3 border-b">تاريخ العملية</th>
                        <th class="p-3 border-b">نوع العملية</th>
                        <th class="p-3 border-b">حالة الدفع</th>
                        <th class="p-3 border-b">مبلغ العملية</th>
                        <th class="p-3 border-b">المرجع</th>
                        <th class="p-3 border-b">وصف الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr class="hover:bg-gray-50">
                            <!-- تاريخ العملية -->
                            <td class="p-2">{{ \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d') }}</td>

                            <!-- نوع العملية بناءً على status -->
                            <td class="p-2 text-red-600 font-medium">
                                @if ($sale->status == 'Issued')
                                    بيع - تم الإصدار
                                @elseif ($sale->status == 'Re-Issued')
                                    بيع - إعادة الإصدار
                                @elseif ($sale->status == 'Re-Route')
                                    بيع - تغيير المسار
                                @elseif ($sale->status == 'Refund-Full')
                                    استرداد كلي - Refund Full
                                @elseif ($sale->status == 'Refund-Partial')
                                    استرداد جزئي - Refund Partial
                                @elseif ($sale->status == 'Void')
                                    ملغي نهائي - Void
                                @elseif ($sale->status == 'Rejected')
                                    مرفوض - Rejected
                                @elseif ($sale->status == 'Approved')
                                    مقبول - Approved
                                @endif
                            </td>

                            <!-- حالة الدفع بناءً على payment_status -->
                            <td class="p-2 text-green-600 font-medium">
                                @if ($sale->payment_method == 'kash')
                                    دفع كامل
                                @elseif ($sale->payment_method == 'part')
                                    دفع جزئي
                                @elseif ($sale->payment_method == 'all')
                                    لم يدفع
                                @endif
                            </td>

                            <!-- مبلغ العملية -->
                            <td class="p-2 text-gray-800">{{ number_format($sale->usd_sell, 2) }} {{ $currency }}
                            </td>

                            <!-- المرجع -->
                            <td class="p-2 text-gray-600">{{ $sale->reference ?? '—' }}</td>

                            <!-- وصف الحالة -->
                            <td class="p-2 text-gray-600">{{ ucfirst($sale->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 p-4">لا توجد عمليات بيع</td>
                        </tr>
                    @endforelse

                    <!-- عرض العمليات المتعلقة بالتحصيل -->
                    @forelse($collections as $collection)
                        <tr class="hover:bg-gray-50">
                            <td class="p-2">{{ \Carbon\Carbon::parse($collection->payment_date)->format('Y-m-d') }}
                            </td>
                            <td class="p-2 text-green-600 font-medium">تحصيل</td>
                            <td class="p-2 text-green-600 font-medium">
                                @if (strpos($collection->note ?? '', 'رصيد الشركة') !== false)
                                    تم السداد من رصيد الشركة
                                @else
                                    تم السداد من العميل
                                @endif
                            </td>
                            <td class="p-2 text-gray-800">{{ number_format($collection->amount, 2) }}
                                {{ $currency }}</td>
                            <td class="p-2 text-gray-600">{{ $collection->sale->reference ?? '—' }}</td>
                            <td class="p-2 text-gray-600">
                                @if (strpos($collection->note ?? '', 'رصيد الشركة') !== false)
                                    تم خصم {{ number_format($collection->amount, 2) }} من رصيد الشركة
                                @else
                                    {{ $collection->note ?? '—' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 p-4">لا توجد تحصيلات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-- 🟨 الملخص -->
    <div>
        <h3 class="text-lg font-semibold text-[rgb(var(--primary-600))] mb-2">ملخص الحساب</h3>
        <div class="bg-white rounded-xl shadow-md p-4 grid md:grid-cols-3 gap-4 text-sm">
            <!-- إجمالي المبيعات -->
            <div class="border-b pb-2">
                <strong>إجمالي المبيعات الفعالة:</strong>
                <span class="text-gray-700 block">{{ number_format($activeSales, 2) }} {{ $currency }}</span>
            </div>

            <!-- إجمالي المدفوعات -->
            <div class="border-b pb-2">
                <strong>إجمالي التحصيل:</strong>
                <span class="text-gray-700 block">
                    {{ number_format($directPayments, 2) }} {{ $currency }} (من العميل)
                </span>
            </div>
            <!-- عرض حالة الرصيد الفارق -->
            <div class="border-b pb-2">
                <strong>الرصيد الفارق:</strong>
                <div class="text-gray-700 block">
                    @if ($netBalance > 0)
                        <span class="text-red-600">مدين: {{ number_format($netBalance, 2) }}
                            {{ $currency }}</span>
                    @elseif ($netBalance < 0)
                        <span class="text-green-700">تم السداد بالكامل</span>
                    @else
                        <span class="text-gray-600">لا يوجد رصيد مستحق</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
