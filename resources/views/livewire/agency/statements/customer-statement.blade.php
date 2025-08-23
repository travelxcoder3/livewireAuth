@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $currency = Auth::user()->agency->currency ?? 'USD';
@endphp

<div class="space-y-6">

    <!-- العنوان وزر الرجوع + التصدير -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            كشف حساب: {{ $customer->name }}
        </h2>

        <div class="flex items-center gap-2">
            @if (!empty($selectedRows))
                <x-primary-button type="button" wire:click="exportPdfAuto"
                                  class="flex items-center gap-2">
                    تصدير PDF
                </x-primary-button>
            @endif

            <a href="{{ route('agency.statements.customers') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg border transition text-sm font-medium
                      bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))] hover:shadow-md hover:text-[rgb(var(--primary-700))]">
                <svg class="h-5 w-5 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>رجوع</span>
            </a>
        </div>
    </div>

    <!-- شريط الفلاتر -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4 items-end">
            <x-input-field name="beneficiary" label="بحث باسم المستفيد" wireModel="beneficiary" placeholder="مثال: أمير علي" />
            <x-date-picker name="fromDate" label="من تاريخ" wireModel="fromDate" />
            <x-date-picker name="toDate" label="إلى تاريخ" wireModel="toDate" />

            <div class="mt-3 flex justify-between">
                <div></div>
                <x-primary-button type="button" wire:click="resetFilters" 
                                :gradient="false" 
                                color="bg-gray-200 hover:bg-gray-300" 
                                textColor="text-gray-800" 
                                class="font-bold">
                    إعادة تعيين الفلاتر
                </x-primary-button>


            </div>
        </div>
    </div>

    <!-- جدول كشف الحساب -->
    <div
        x-data="{ sel: @entangle('selectedRows') }"
        x-init="
            const header = $refs.selAll;
            const sync = () => {
                const total = {{ count($statement) }};
                const n = Array.isArray(sel) ? sel.length : 0;
                header.checked       = (total > 0 && n === total);
                header.indeterminate = (n > 0 && n < total);
            };
            sync();
            $watch('sel', sync);
        "
        class="overflow-x-auto rounded-xl shadow bg-white"
    >
          <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
              <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-2 py-1 text-center">
                        <input type="checkbox"
                               x-ref="selAll"
                               @click.prevent="$wire.toggleSelectAll()"
                               class="rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]"
                               style="accent-color: rgb(var(--primary-500));"
                               title="تحديد/إلغاء الكل">
                    </th>
                    <th class="px-2 py-1">رقم</th>
                    <th class="px-2 py-1">تاريخ الخدمة</th>
                    <th class="px-2 py-1">الوصف</th>
                    <th class="px-2 py-1">عليه</th>
                    <th class="px-2 py-1">له</th>
                    <th class="px-2 py-1">الرصيد</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($statement as $i => $line)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" wire:model.live="selectedRows" value="{{ $i }}"
                                   class="rounded border-gray-300 focus:ring-[rgb(var(--primary-500))]"
                                   style="accent-color: rgb(var(--primary-500));">
                        </td>
                        <td class="px-3 py-2">{{ $line['no'] }}</td>
                        <td class="px-3 py-2">{{ $line['date'] }}</td>
                        <td class="px-3 py-2">{{ $line['desc'] }}</td>
                        <td class="px-3 py-2 text-blue-700">{{ number_format($line['debit'], 2) }} {{ $currency }}</td>
                        <td class="px-3 py-2 text-green-700">{{ number_format($line['credit'], 2) }} {{ $currency }}</td>
                       <td class="px-3 py-2 font-semibold">{{ number_format($line['balance'], 2) }} {{ $currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-6">لا توجد عمليات ضمن النطاق.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
