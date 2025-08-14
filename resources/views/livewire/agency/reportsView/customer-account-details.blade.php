<div class="space-y-6">
    <!-- العنوان العلوي -->
 <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-bold text-[rgb(var(--primary-700))] border-b border-[rgba(var(--primary-200),0.5)] pb-2">
        التقرير المالي لمشتريات العميل
    </h2>
    <div class="flex gap-2">
        <!-- زر الرجوع -->
        <button onclick="history.back();"
            class="group flex items-center gap-2 text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
            <i class="fas fa-arrow-left transform transition-transform duration-300 group-hover:-translate-x-1"></i>
            <span>رجوع</span>
        </button>

        <!-- زر تنزيل PDF -->
        <a href="{{ route('agency.reports.customer-accounts.pdf', $customer->id) }}"
            class="group flex items-center gap-2 text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
            <i class="fas fa-file-pdf transform transition-transform duration-300 group-hover:scale-110"></i>
            <span>تنزيل PDF</span>
        </a>
    </div>
</div>



    <!-- بيانات العميل -->
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

    <!-- العمليات -->
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
@forelse ($sortedTransactions as $row)
    <tr class="hover:bg-gray-50">
        {{-- التاريخ --}}
        <td class="p-2">{{ \Carbon\Carbon::parse($row['date'])->format('Y-m-d') }}</td>

        {{-- نوع العملية مشتق من الوصف --}}
        <td class="p-2 font-medium">
            {{ str_starts_with($row['desc'], 'سداد') ? 'تحصيل' : 'بيع/حدث عملية' }}
        </td>

        {{-- حالة الدفع كما بُنيت في الصف --}}
        <td class="p-2 font-medium">
            {{ $row['status'] }}
        </td>

        {{-- مبلغ العملية: المدين = مبيعات، الدائن = سداد/استرداد --}}
        <td class="p-2 text-gray-800">
            @php
                $amt = $row['debit'] > 0 ? $row['debit'] : $row['credit'];
            @endphp
            {{ number_format($amt, 2) }} {{ $currency }}
        </td>

        {{-- المرجع غير متاح في الصف الموحد، اتركه شرطة --}}
        <td class="p-2 text-gray-600">—</td>

        {{-- وصف الحالة الكامل --}}
        <td class="p-2 text-gray-600">{{ $row['desc'] }}</td>
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

    <!-- الملخص -->
    <div>
        <h3 class="text-lg font-semibold text-[rgb(var(--primary-600))] mb-2">ملخص الحساب</h3>
        <div class="bg-white rounded-xl shadow-md p-4 grid md:grid-cols-3 gap-4 text-sm">
            <div class="border-b pb-2">
                <strong>إجمالي المبيعات:</strong>
                <span class="text-gray-700 block">{{ number_format($activeSales, 2) }} {{ $currency }}</span>
                <small class="text-xs text-gray-500">(جميع مبالغ عمليات البيع)</small>
            </div>
            <div class="border-b pb-2">
                <strong>إجمالي التحصيل:</strong>
                <span class="text-gray-700 block">{{ number_format($netPayments, 2) }} {{ $currency }}</span>
                <small class="text-xs text-gray-500">(المبالغ التي دفعها العميل فعلياً)</small>
            </div>

            <div class="border-b pb-2">
                <strong>الرصيد الفارق:</strong>
                <div class="text-gray-700 block">
                    @if ($netBalance > 0)
                        <span class="text-red-600">مدين: {{ number_format($netBalance, 2) }} {{ $currency }}</span>
                    @elseif($netBalance == 0)
                        <span class="text-green-700">تم السداد بالكامل</span>
                    @else
                        <span class="text-gray-600">لا يوجد رصيد مستحق</span>
                    @endif
                </div>
                <small class="text-xs text-gray-500">(إجمالي المبيعات - إجمالي التحصيل)</small>
            </div>
            <div class="border-b pb-2">
                <strong>رصيد العميل لدى الشركة:</strong>
                <span class="text-gray-700 block">{{ number_format($availableBalanceToPayOthers, 2) }} {{ $currency }}</span>
                <small class="text-xs text-gray-500">(رصيد العميل بناءً على الاستردادات)</small>
            </div>
        </div>
    </div>
</div>
