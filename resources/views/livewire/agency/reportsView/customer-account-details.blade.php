@php
    $accountNumber =
        \App\Models\Customer::where('agency_id', $customer->agency_id)
            ->orderBy('created_at')
            ->pluck('id')
            ->search($customer->id) + 1;
    $currency = Auth::user()->agency->currency ?? 'USD';
@endphp

<div class="space-y-6">

    <!-- العنوان العلوي -->
    <div class="flex justify-between items-center mb-4">
        <!-- عنوان الصفحة -->
        <h2
            class="text-2xl font-bold text-[rgb(var(--primary-700))] border-b border-[rgba(var(--primary-200),0.5)] pb-2">
            كشف حساب العميل
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


    <!-- بيانات العميل -->
    <div class="bg-white rounded-xl shadow-md p-4 grid md:grid-cols-3 gap-4 text-sm text-center">
        <div><span class="text-gray-500">اسم العميل:</span> <strong>{{ $customer->name }}</strong></div>
        <div><span class="text-gray-500">نوع الحساب:</span> {{ $customer->has_commission ? 'عمولة' : 'عادي' }}</div>
        <div><span class="text-gray-500">رقم الحساب:</span> {{ $accountNumber }}</div>
        <div><span class="text-gray-500">تاريخ فتح الحساب:</span> {{ $customer->created_at->format('Y-m-d') }}</div>
        <div><span class="text-gray-500">الجوال:</span> {{ $customer->phone ?? '-' }}</div>
        <div><span class="text-gray-500">البريد الإلكتروني:</span> {{ $customer->email ?? '-' }}</div>
        <div><span class="text-gray-500">العملة:</span> {{ Auth::user()?->agency?->currency ?? 'USD' }}</div>
    </div>

    <!-- جدول العمليات -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <table class="w-full text-sm border text-center">
            <thead class="bg-[rgb(var(--primary-500))] text-white text-sm">
                <tr>
                    <th class="p-3 border-b">النوع</th>
                    <th class="p-3 border-b">التاريخ</th>
                    <th class="p-3 border-b">المبلغ</th>
                    <th class="p-3 border-b">الوصف</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="p-2 text-red-600 font-medium">بيع</td>
                        <td class="p-2">{{ $sale->sale_date }}</td>
                        <td class="p-2 text-gray-800">{{ number_format($sale->usd_sell, 2) }} {{ $currency }}</td>
                        <td class="p-2 text-gray-600">{{ ucfirst($sale->status) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-400 p-4">لا توجد عمليات بيع</td>
                    </tr>
                @endforelse

                @forelse($collections as $collection)
                    <tr class="hover:bg-gray-50">
                        <td class="p-2 text-green-600 font-medium">تحصيل</td>
                        <td class="p-2">{{ $collection->payment_date }}</td>
                        <td class="p-2 text-gray-800">${{ number_format($collection->amount, 2) }}</td>
                        <td class="p-2 text-gray-600">{{ $collection->note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-400 p-4">لا توجد تحصيلات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ملخص -->
    <div class="bg-white rounded-xl shadow-md p-4 flex flex-col md:flex-row justify-between text-sm gap-3">
        <div>
            <strong>إجمالي المبيعات:</strong>
            <span class="text-gray-700">${{ number_format($sales->sum('usd_sell'), 2) }}</span>
        </div>
        <div>
            <strong>إجمالي التحصيل:</strong>
            <span class="text-gray-700">${{ number_format($collections->sum('amount'), 2) }}</span>
        </div>
        <div>
            <strong>الرصيد:</strong>
            @php
                $balance = $sales->sum('usd_sell') - $collections->sum('amount');
            @endphp
            <span
                class="{{ $balance > 0 ? 'text-red-600' : ($balance < 0 ? 'text-green-600' : 'text-gray-600') }} font-semibold">
                ${{ number_format(abs($balance), 2) }}
                {{ $balance > 0 ? 'على العميل' : ($balance < 0 ? 'للعميل' : '') }}
            </span>
        </div>
    </div>
</div>
