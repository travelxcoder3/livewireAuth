<div>
<div class="space-y-6">
    <h2 class="text-xl font-bold text-gray-700">تقرير العمولات  للموظفين لشهر {{ $month }}/{{ $year }}</h2>

    <table class="w-full text-sm border border-gray-200 rounded">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-3 py-2 text-right">الموظف</th>
                <th class="px-3 py-2 text-right">الهدف</th>
                <th class="px-3 py-2 text-right">نسبة العمولة</th>
                <th class="px-3 py-2 text-right">الربح الكلي</th>
                <th class="px-3 py-2 text-right">الربح المحصل</th>
                <th class="px-3 py-2 text-right">العمولة المتوقعة</th>
                <th class="px-3 py-2 text-right">العمولة المستحقة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commissionData as $item)
                <tr class="border-t">
                    <td class="px-3 py-2">{{ $item['user'] }}</td>
                    <td class="px-3 py-2">{{ number_format($item['target'], 2) }}</td>
                    <td class="px-3 py-2">{{ $item['rate'] }}%</td>
                    <td class="px-3 py-2">{{ number_format($item['total_profit'], 2) }}</td>
                    <td class="px-3 py-2">{{ number_format($item['collected_profit'], 2) }}</td>
                    <td class="px-3 py-2 text-green-600 font-bold">{{ number_format($item['expected_commission'], 2) }}</td>
                    <td class="px-3 py-2 text-blue-600 font-bold">{{ number_format($item['earned_commission'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(count($customerCommissionData) > 0)
    <h2 class="text-xl font-bold text-gray-700 mt-10">عمولات العملاء {{ $month }}/{{ $year }}</h2>
    <table class="w-full text-sm border border-gray-200 rounded mt-4">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-3 py-2 text-right">العميل</th>
                <th class="px-3 py-2 text-right">المبلغ</th>
                <th class="px-3 py-2 text-right">الربح</th>
                <th class="px-3 py-2 text-right">العمولة المستحقة</th>
                <th class="px-3 py-2 text-right">الحالة</th>
                <th class="px-3 py-2 text-right">التاريخ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customerCommissionData as $item)
                <tr class="border-t">
                    <td class="px-3 py-2">{{ $item['customer'] }}</td>
                    <td class="px-3 py-2">{{ number_format($item['amount'], 2) }}</td>
                    <td class="px-3 py-2">{{ number_format($item['profit'], 2) }}</td>
                    <td class="px-3 py-2 text-blue-600 font-bold">{{ number_format($item['commission'], 2) }}</td>
                    <td class="px-3 py-2">
                        @if ($item['status'] == 'تم التحصيل')
                            <span class="text-green-600 font-semibold">تم التحصيل</span>
                        @else
                            <span class="text-red-600 font-semibold">غير محصل</span>
                        @endif
                    </td>
                    <td class="px-3 py-2">{{ $item['date'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

</div?