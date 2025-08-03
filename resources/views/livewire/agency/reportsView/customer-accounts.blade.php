@php
    use App\Services\ThemeService;
    use App\Tables\CustomerAccountsTable;
    $columns = CustomerAccountsTable::columns();
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);

    $fieldClass =
        'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
    $labelClass =
        'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
@endphp

<div class="space-y-6">
    <!-- عنوان التقرير -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            تقرير حسابات العملاء
        </h2>
    </div>
    <!-- فلاتر البحث -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4">
            <!-- اسم العميل -->
            <div class="relative">
                <label class="block mb-1 text-sm font-medium" style="color: rgb(var(--primary-700));">
                    اسم العميل
                </label>
                <input type="text" wire:model.debounce.500ms="clientName" placeholder="ابحث عن اسم العميل"
                    class="w-full px-4 py-2 border rounded-md shadow-sm focus:outline-none focus:ring focus:border-blue-300 {{ $fieldClass }}" />
            </div>
            <!-- نوع العميل -->
            <div class="relative">
                <label class="block mb-1 text-sm font-medium" style="color: rgb(var(--primary-700));">
                    نوع العميل
                </label>
                <select wire:model="customerTypeId"
                    class="w-full px-4 py-2 border rounded-md shadow-sm focus:outline-none focus:ring focus:border-blue-300 {{ $fieldClass }}">
                    <option value="">نوع العميل</option>
                    @foreach ($customerTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <!-- من تاريخ -->
            <div class="relative">
                <label class="block mb-1 text-sm font-medium" style="color: rgb(var(--primary-700));">
                    من تاريخ
                </label>
                <input type="date" wire:model.live="fromDate"
                    class="w-full px-4 py-2 border rounded-md shadow-sm focus:outline-none focus:ring focus:border-blue-300 {{ $fieldClass }}" />
            </div>
            <!-- إلى تاريخ -->
            <div class="relative">
                <label class="block mb-1 text-sm font-medium" style="color: rgb(var(--primary-700));">
                    إلى تاريخ
                </label>
                <input type="date" wire:model.live="toDate"
                    class="w-full px-4 py-2 border rounded-md shadow-sm focus:outline-none focus:ring focus:border-blue-300 {{ $fieldClass }}" />
            </div>
        </div>
        <!-- زر إعادة تعيين الفلاتر -->
        <div class="flex justify-end mt-4">
            <button wire:click="resetFilters"
                class="text-white font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm"
                style="background-color: rgb(var(--primary-500));"
                onmouseover="this.style.backgroundColor='rgb(var(--primary-600))'"
                onmouseout="this.style.backgroundColor='rgb(var(--primary-500))'">
                إعادة تعيين الفلاتر
            </button>
        </div>
    </div>

    <!-- جدول التقرير -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="bg-white rounded-xl shadow-md overflow-auto">
            <table class="w-full text-sm text-center table-auto border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-[rgb(var(--primary-100))] text-gray-700">
                    <tr class="text-white bg-[rgb(var(--primary-700))]">
                        <th class="py-3 px-4 border-b">اسم العميل</th>
                        <th class="py-3 px-4 border-b" colspan="2">الرصيد</th>
                        <th class="py-3 px-4 border-b">الإجمالي</th>
                        <th class="py-3 px-4 border-b">تاريخ آخر عملية بيع</th>
                        <th class="py-3 px-4 border-b">العملة</th>
                        <th class="py-3 px-4 border-b">الإجراء</th>
                    </tr>
                    <tr class="bg-gray-50 text-xs text-gray-500">
                        <th></th>
                        <th class="py-1 px-4 border-b">له</th>
                        <th class="py-1 px-4 border-b">عليه</th>
                        <th colspan="3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($customers as $customer)
                        @php
                            $netBalance = $customer['net_balance'];
                            $balanceFor = $netBalance < 0 ? number_format(abs($netBalance), 2) : '0.00';
                            $balanceOn = $netBalance > 0 ? number_format($netBalance, 2) : '0.00';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 font-medium text-right">{{ $customer['name'] }}</td>
                            <td class="py-2 px-4 text-green-600 font-mono">{{ $balanceFor }}</td>
                            <td class="py-2 px-4 text-red-600 font-mono">{{ $balanceOn }}</td>
                            <td class="py-2 px-4 font-mono text-gray-800">{{ number_format($customer['total'], 2) }}
                            </td>
                            <td class="py-2 px-4 text-gray-600">
                                {{ \Carbon\Carbon::parse($customer['last_sale_date'])->format('Y-m-d') }}
                            </td>
                            <td class="py-2 px-4 text-gray-600">{{ $customer['currency'] }}</td>
                            <td class="py-2 px-4">
                                <a href="{{ route('agency.reports.customer-accounts.details', $customer['id']) }}"
                                    class="bg-white border border-[rgb(var(--primary-500))] text-[rgb(var(--primary-500))] px-3 py-1 rounded transition duration-200 hover:shadow-md"
                                    style="--tw-bg-opacity: 1;"
                                    onmouseover="this.style.backgroundColor='rgba(var(--primary-500), 0.08)'"
                                    onmouseout="this.style.backgroundColor='white'">
                                    كشف الحساب
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-500 py-4">لا توجد بيانات عملاء حاليًا.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>

</div>
