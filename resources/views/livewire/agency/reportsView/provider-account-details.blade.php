<div class="space-y-6">
    <!-- العنوان -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-[rgb(var(--primary-700))] border-b border-[rgba(var(--primary-200),0.5)] pb-2">
            التقرير المالي لمشتريات المزوّد
        </h2>
        <div class="flex gap-2">
            <button onclick="history.back();"
                class="group flex items-center gap-2 text-white font-bold px-4 py-2 rounded-xl shadow-md text-sm"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                <i class="fas fa-arrow-left group-hover:-translate-x-1 transition"></i><span>رجوع</span>
            </button>
            <a href="{{ route('agency.reports.provider-accounts.pdf', $provider->id) }}"
   class="group flex items-center gap-2 text-white font-bold px-4 py-2 rounded-xl shadow-md text-sm"
   style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
   <i class="fas fa-file-pdf group-hover:scale-110 transition"></i><span>تنزيل PDF</span>
</a>
        </div>
    </div>

    <!-- بيانات المزوّد -->
    <div>
        <h3 class="text-lg font-semibold text-[rgb(var(--primary-600))] mb-2 px-4">بيانات المزوّد</h3>
        <div class="bg-white rounded-xl shadow-md p-4 grid md:grid-cols-3 gap-4 text-sm text-center">
            <div><span class="text-gray-500">اسم المزوّد:</span> <strong>{{ $provider->name }}</strong></div>
            <div><span class="text-gray-500">العملة:</span> {{ $currency }}</div>
            <div><span class="text-gray-500">رقم الحساب:</span> {{ $provider->id }}</div>
        </div>
    </div>

    <!-- العمليات -->
    <div>
        <h3 class="text-lg font-semibold text-[rgb(var(--primary-600))] mb-2">عمليات الشراء والسداد</h3>
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <table class="w-full text-sm border text-center">
                <thead class="bg-gray-100 text-gray-800">
                    <tr>
                        <th class="p-3 border-b">تاريخ العملية</th>
                        <th class="p-3 border-b">نوع العملية</th>
                        <th class="p-3 border-b">الحالة</th>
                        <th class="p-3 border-b">مبلغ العملية</th>
                        <th class="p-3 border-b">المرجع</th>
                        <th class="p-3 border-b">الوصف</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="p-2">{{ \Carbon\Carbon::parse($r['date'])->format('Y-m-d') }}</td>
                            <td class="p-2 font-medium">{{ $r['type'] }}</td>
                            <td class="p-2 font-medium">{{ $r['status'] }}</td>
                            <td class="p-2 text-gray-800">{{ number_format($r['amount'],2) }} {{ $currency }}</td>
                            <td class="p-2 text-gray-600">{{ $r['ref'] ?? '—' }}</td>
                            <td class="p-2 text-gray-600 text-right">{{ $r['desc'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-gray-400 p-4">لا توجد عمليات</td></tr>
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
                <strong>إجمالي الشراء:</strong>
                <span class="block">{{ number_format($tot_buy,2) }} {{ $currency }}</span>
            </div>
            <div class="border-b pb-2">
                <strong>إجمالي الدائن للمزوّد:</strong>
                <span class="block">{{ number_format($tot_credit,2) }} {{ $currency }}</span>
                <small class="text-xs text-gray-500">(استرداد + إلغاء + مدفوع للمزوّد)</small>
            </div>
            <div class="border-b pb-2">
                <strong>الرصيد:</strong>
                @php $b=$balance; @endphp
                <div>
                    @if ($b > 0)
                        <span class="text-emerald-700">له: {{ number_format($b,2) }} {{ $currency }}</span>
                    @elseif ($b < 0)
                        <span class="text-rose-700">عليه: {{ number_format(abs($b),2) }} {{ $currency }}</span>
                    @else
                        <span class="text-gray-700">لا رصيد</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
