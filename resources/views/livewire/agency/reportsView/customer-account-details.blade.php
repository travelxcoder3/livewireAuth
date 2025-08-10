@php
    $accountNumber =
        \App\Models\Customer::where('agency_id', $customer->agency_id)
            ->orderBy('created_at')
            ->pluck('id')
            ->search($customer->id) + 1;
    $currency = Auth::user()->agency->currency ?? 'USD';

    // حساب إجمالي المبيعات (جميع عمليات البيع الفعالة)
    $activeSales = $sales->whereNotIn('status', ['Void'])->sum('usd_sell');

    // حساب المدفوعات
    $directPayments = $collections->sum('amount');
    $partialPayments = $sales->where('payment_method', 'part')->sum('amount_paid');

    // حساب المبالغ المستردة
    $refundPayments = $sales->where('status', 'Refund-Full')->sum('usd_sell');

    // حساب التحصيل الصافي (المدفوعات - المستردات)
    $netPayments = $directPayments + $partialPayments - $refundPayments;

    // التأكد من أن التحصيل لا يتجاوز إجمالي المبيعات
    $totalPayments = min($activeSales, max(0, $netPayments));

    // حساب الرصيد الفارق
    $netBalance = $activeSales - $totalPayments;

    // تحضير بيانات العمليات بدون ترتيب حسب التاريخ
    $transactions = collect();

    // إضافة عمليات البيع
    foreach ($sales as $sale) {
        $transactions->push([
            'created_at' => $sale->created_at,
            'date' => $sale->sale_date,
            'type' => 'sale',
            'data' => $sale,
            'amount' => $sale->usd_sell,
            'is_refund' => in_array($sale->status, ['Refund-Full', 'Refund-Partial']),
            'is_partial' => $sale->payment_method == 'part',
            'partial_amount' => $sale->amount_paid ?? 0,
        ]);
    }

    // إضافة عمليات التحصيل
    foreach ($collections as $collection) {
        $transactions->push([
            'created_at' => $collection->created_at,
            'date' => $collection->payment_date,
            'type' => 'collection',
            'data' => $collection,
            'amount' => $collection->amount,
            'is_refund' => false,
            'is_partial' => false,
            'partial_amount' => 0,
        ]);
    }

    // ترتيب العمليات حسب وقت حدوثها (created_at)
    $sortedTransactions = $transactions->sortBy('created_at');
@endphp

<div class="space-y-6">
    <!-- 🔵 العنوان العلوي -->
    <div class="flex justify-between items-center mb-4">
        <h2
            class="text-2xl font-bold text-[rgb(var(--primary-700))] border-b border-[rgba(var(--primary-200),0.5)] pb-2">
            كشف حساب العميل
        </h2>
        <div class="flex gap-2">
            <a href="{{ route('agency.reports.customer-accounts.pdf', $customer->id) }}" target="_blank"
                class="flex items-center gap-2 text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                <i class="fas fa-file-export"></i>
                <span>تصدير التقرير</span>
            </a>
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
            <div><span class="text-gray-500">العملة:</span> {{ $currency }}</div>
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
                    @forelse ($sortedTransactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="p-2">{{ \Carbon\Carbon::parse($transaction['date'])->format('Y-m-d') }}</td>

                            @if ($transaction['type'] == 'sale')
                                <td
                                    class="p-2 font-medium {{ $transaction['is_refund'] ? 'text-blue-600' : 'text-red-600' }}">
                                    @switch($transaction['data']->status)
                                        @case('Issued')
                                            بيع - تم الإصدار
                                        @break

                                        @case('Re-Issued')
                                            بيع - إعادة الإصدار
                                        @break

                                        @case('Re-Route')
                                            بيع - تغيير المسار
                                        @break

                                        @case('Refund-Full')
                                            استرداد كلي
                                        @break

                                        @case('Refund-Partial')
                                            استرداد جزئي
                                        @break

                                        @case('Void')
                                            ملغي نهائي
                                        @break

                                        @case('Rejected')
                                            مرفوض
                                        @break

                                        @case('Approved')
                                            مقبول
                                        @break

                                        @default
                                            بيع
                                    @endswitch
                                </td>

                                <td
                                    class="p-2 font-medium
                                    @if (in_array($transaction['data']->status, ['Refund-Full', 'Refund-Partial']) ||
                                            $transaction['data']->payment_method == 'kash') text-green-600
                                    @elseif($transaction['data']->payment_method == 'part') text-yellow-600
                                    @else text-gray-600 @endif">
                                    @if (in_array($transaction['data']->status, ['Refund-Full', 'Refund-Partial']))
                                        دفع كامل
                                    @elseif($transaction['data']->payment_method == 'kash')
                                        دفع كامل
                                    @elseif($transaction['data']->payment_method == 'part')
                                        دفع جزئي
                                    @else
                                        لم يدفع
                                    @endif
                                </td>

                                <td class="p-2 text-gray-800">
                                    @if ($transaction['is_refund'])
                                        {{ number_format($transaction['amount'], 2) }}
                                    @elseif($transaction['is_partial'])
                                        {{ number_format($transaction['partial_amount'], 2) }}
                                    @else
                                        {{ number_format($transaction['amount'], 2) }}
                                    @endif
                                    {{ $currency }}
                                </td>

                                <td class="p-2 text-gray-600">{{ $transaction['data']->reference ?? '—' }}</td>
                                <td class="p-2 text-gray-600">{{ ucfirst($transaction['data']->status) }}</td>
                            @else
                                <td class="p-2 text-green-600 font-medium">تحصيل</td>
                                <td class="p-2 text-green-600 font-medium">تم السداد</td>
                                <td class="p-2 text-gray-800">
                                    {{ number_format($transaction['amount'], 2) }} {{ $currency }}
                                </td>
                                <td class="p-2 text-gray-600">{{ $transaction['data']->sale->reference ?? '—' }}</td>
                                <td class="p-2 text-gray-600">
                                    @if (strpos($transaction['data']->note ?? '', 'رصيد الشركة') !== false)
                                        تم خصم {{ number_format($transaction['amount'], 2) }} من رصيد الشركة
                                    @else
                                        {{ $transaction['data']->note ?? '—' }}
                                    @endif
                                </td>
                            @endif
                        </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-400 p-4">لا توجد عمليات</td>
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
                <div class="border-b pb-2">
                    <strong>إجمالي المبيعات:</strong>
                    <span class="text-gray-700 block">{{ number_format($activeSales, 2) }} {{ $currency }}</span>
                    <small class="text-xs text-gray-500">(جميع مبالغ عمليات البيع )</small>
                </div>
                <div class="border-b pb-2">
                    <strong>إجمالي التحصيل:</strong>
                    <span class="text-gray-700 block">{{ number_format($totalPayments, 2) }} {{ $currency }}</span>
                    <small class="text-xs text-gray-500">(المبالغ التي دفعها العميل فعلياً)</small>
                </div>

                <div class="border-b pb-2">
                    <strong>الرصيد الفارق:</strong>
                    <div class="text-gray-700 block">
                        @if ($netBalance > 0)
                            <span class="text-red-600">مدين: {{ number_format($netBalance, 2) }}
                                {{ $currency }}</span>
                        @elseif($netBalance < 0)
                            <span class="text-green-700">تم السداد بالكامل </span>
                        @else
                            <span class="text-gray-600">لا يوجد رصيد مستحق</span>
                        @endif
                    </div>
                    <small class="text-xs text-gray-500">(إجمالي المبيعات - إجمالي التحصيل)</small>
                </div>
            </div>
        </div>
    </div>
