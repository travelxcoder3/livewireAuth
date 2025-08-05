@php
    use App\Tables\CustomerAccountsCpllectionsTable;
    $columns = CustomerAccountsCpllectionsTable::columns();
@endphp

<div class="space-y-6">

    <!-- العنوان الرئيسي + زر الرجوع -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            جميع الحسابات
        </h2>
        <div class="flex justify-end mb-4">
            <a href="{{ route('agency.collections') }}"
                class="flex items-center gap-2 px-4 py-2 rounded-lg border transition duration-200 text-sm font-medium
                      bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))] hover:shadow-md hover:text-[rgb(var(--primary-700))]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform rotate-180" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>رجوع</span>
            </a>
        </div>
    </div>

    <!-- مربع البحث عن العملاء -->
    <div class="flex items-center gap-4 mb-4">
        <input type="text" wire:model.live.debounce.500ms="search" placeholder="بحث باسم العميل..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring text-sm" />

        @if ($search)
            <button wire:click="$set('search', '')" class="text-gray-500 hover:text-gray-700 text-sm">
                إلغاء البحث
            </button>
        @endif
    </div>


    <!-- جدول الحسابات -->
    <div class="overflow-x-auto rounded-xl shadow-lg bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-right">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    @foreach ($columns as $col)
                        <th class="px-3 py-2">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($customers as $index => $row)
                    <tr class="hover:bg-gray-50">
                        @foreach ($columns as $col)
                            <td class="px-3 py-2">
                                @php
                                    $key = $col['key'];
                                    $value = data_get($row, $key);
                                    if ($key === 'index') {
                                        $display = $index + 1;
                                    } elseif ($key === 'type') {
                                        $display = $value > 0 ? 'على العميل' : ($value < 0 ? 'للعميل' : 'متوازن');
                                    } elseif ($key === 'actions') {
                                        $display =
                                            '<a href="' .
                                            url("/agency/customer-accounts/{$row->id}/history") .
                                            '"
                                            class="transition duration-200 font-semibold"
                                            style="color: rgb(var(--primary-600));"
                                            onmouseover="this.style.color=\'rgb(var(--primary-700))\'"
                                            onmouseout="this.style.color=\'rgb(var(--primary-600))\'">تفاصيل</a>';
                                    } else {
                                        $display = $value;
                                    }
                                @endphp
                                {!! $display !!}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center py-4 text-gray-400">
                            لا توجد بيانات لعرضها
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
