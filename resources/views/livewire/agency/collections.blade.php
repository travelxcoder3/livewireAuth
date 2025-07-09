
@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);

    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
    $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
    $containerClass = 'relative mt-1';
@endphp

<div class="space-y-6">
    <!-- عنوان الصفحة -->
    <h2 class="text-xl font-bold text-gray-700 border-b pb-2" style="color: rgb(var(--primary-700));">
        عرض التحصيلات
    </h2>

    <!-- الفلاتر -->
    <div class="bg-white rounded-xl shadow-md p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="{{ $containerClass }}">
            <input type="text" wire:model.live="search" class="{{ $fieldClass }}" placeholder="اسم العميل أو المستفيد">
            <label class="{{ $labelClass }}">بحث</label>
        </div>

        <div class="{{ $containerClass }}">
            <select wire:model.live="customerType" class="{{ $fieldClass }}">
                <option value="">كل أنواع العملاء</option>
                @foreach($customerTypes as $item)
                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                @endforeach
            </select>
            <label class="{{ $labelClass }}">نوع العميل</label>
        </div>

        <div class="{{ $containerClass }}">
            <select wire:model.live="debtType" class="{{ $fieldClass }}">
                <option value="">كل أنواع المديونية</option>
                @foreach($debtTypes as $item)
                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                @endforeach
            </select>
            <label class="{{ $labelClass }}">نوع المديونية</label>
        </div>

        <div class="{{ $containerClass }}">
            <input type="date" wire:model.live="startDate" class="{{ $fieldClass }}">
            <label class="{{ $labelClass }}">من تاريخ</label>
        </div>

        <div class="{{ $containerClass }}">
            <input type="date" wire:model.live="endDate" class="{{ $fieldClass }}">
            <label class="{{ $labelClass }}">إلى تاريخ</label>
        </div>

        <div class="col-span-1 md:col-span-4 flex justify-end gap-2 mt-2">
            <button wire:click="resetFilters" class="bg-gray-100 hover:bg-gray-200 text-sm px-4 py-1.5 rounded shadow">
                إعادة تعيين الفلاتر
            </button>
        </div>
    </div>

    <!-- جدول التحصيلات -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-600 text-xs">
                <tr class="text-center">
                    <th class="px-2 py-2">#</th>
                    <th class="px-2 py-2">اسم العميل</th>
                    <th class="px-2 py-2">الرصيد</th>
                    <th class="px-2 py-2">آخر سداد</th>
                    <th class="px-2 py-2">عمر الدين</th>
                    <th class="px-2 py-2">نوع العميل</th>
                    <th class="px-2 py-2">نوع المديونية</th>
                    <th class="px-2 py-2">تجاوب العميل</th>
                    <th class="px-2 py-2">نوع الارتباط</th>
                    <th class="px-2 py-2">الإجراء</th>
                </tr>
            </thead>
            <tbody class="text-center text-xs divide-y divide-gray-100">
                @forelse($sales as $index => $sale)
                    <tr>
                        <td class="px-2 py-2">{{ $loop->iteration }}</td>
                        <td class="px-2 py-2">{{ $sale->beneficiary_name ?? '-' }}</td>
                       @php
                            $totalInvoice = $sale->usd_sell ?? 0;
                            $paidFromSales = $sale->amount_received ?? 0;
                            $paidFromCollections = $sale->collections->sum('amount');
                            $paidTotal = $paidFromSales + $paidFromCollections;
                            $remaining = $totalInvoice - $paidTotal;
                        @endphp

                        <td class="px-2 py-2 font-bold text-red-600">
                            {{ number_format($remaining, 2) }}
                        </td>

                        <td class="px-2 py-2">{{ optional($sale->collections->last())->payment_date ?? '-' }}</td>
                        <td class="px-2 py-2">
                            @php
                                $debtAge = round(abs(now()->diffInDays($sale->sale_date, false)));
                            @endphp
                            {{ $debtAge }} يوم
                        </td>
                        <td class="px-2 py-2">{{ optional($sale->collections->last()?->customerType)->label ?? '-' }}</td>
                        <td class="px-2 py-2">{{ optional($sale->collections->last()?->debtType)->label ?? '-' }}</td>
                        <td class="px-2 py-2">{{ optional($sale->collections->last()?->customerResponse)->label ?? '-' }}</td>
                        <td class="px-2 py-2">{{ optional($sale->collections->last()?->customerRelation)->label ?? '-' }}</td>
                        <td class="px-2 py-2">
                         <a href="{{ route('agency.collection.details', $sale->id) }}"
                            class="text-white text-xs px-3 py-1 bg-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-600))] rounded-lg shadow">
                                تفاصيل المديونية
                            </a>

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-4 text-center text-gray-400">لا توجد بيانات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($sales->hasPages())
            <div class="px-4 py-2 border-t border-gray-200">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>
