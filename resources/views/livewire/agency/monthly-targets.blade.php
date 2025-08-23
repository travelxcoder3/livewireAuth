<div>
    {{-- توست نجاح بنفس صفحة المبيعات --}}
    @if(!empty($successMessage))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 2000)"
             x-show="show"
             x-transition
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm z-50"
             style="background-color: rgb(var(--primary-500));">
            {{ $successMessage }}
        </div>
    @endif

    {{-- العنوان + أدوات الشهر --}}
    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-12 gap-2 lg:gap-4 items-center">
        <div class="col-span-12 md:col-span-4 lg:col-span-3 flex items-center justify-start">
            <h1 class="text-2xl font-bold text-[rgb(var(--primary-700))]">أهداف الموظفين الشهرية</h1>
        </div>

        <div class="col-span-12 md:col-span-8 lg:col-span-6">
            <div class="bg-white border shadow rounded-xl px-2 py-2 flex flex-wrap justify-center gap-x-2 gap-y-2 items-center text-xs font-semibold text-gray-700 whitespace-nowrap">
                <input type="number" min="2000" wire:model.lazy="year"
                       class="w-24 rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none" />
                <input type="number" min="1" max="12" wire:model.lazy="month"
                       class="w-16 rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none" />

                <button wire:click="loadRows"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-3 py-2 rounded-xl shadow transition duration-300 text-sm">
                    تحميل
                </button>

                <button wire:click="copyFromPrev"
                        class="text-white font-bold px-3 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                        style="background: linear-gradient(to right, #f59e0b 0%, #d97706 100%);">
                    نسخ من الشهر السابق
                </button>

                <button wire:click="saveAll"
                        class="text-white font-bold px-3 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                    حفظ الكل
                </button>
            </div>
        </div>

        <div class="col-span-12 md:col-span-4 lg:col-span-3"></div>
    </div>

    {{-- الجدول بنفس تصميم x-data-table (المبيعات) --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-2 py-1">الموظف</th>
                        <th class="px-2 py-1">الهدف الأساسي</th>
                        <th class="px-2 py-1">هدف المقارنة</th>
                        <th class="px-2 py-1">العموله الخاصة للموظف </th>
                        <th class="px-2 py-1 text-center">قفل</th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($rows as $i => $r)
                        <tr class="hover:bg-gray-50">
                            {{-- الموظف --}}
                            <td class="px-2 py-1 font-medium text-gray-800">
                                {{ $r['name'] }}
                            </td>

                            {{-- الهدف الأساسي --}}
                            <td class="px-2 py-1">
                                <input type="number" step="0.01" wire:model.lazy="rows.{{ $i }}.main_target"
                                       class="w-36 rounded-lg border border-gray-300 px-2 py-1 text-xs bg-white focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none"
                                       @disabled($r['locked'])>
                            </td>

                            {{-- هدف المقارنة --}}
                            <td class="px-2 py-1">
                                <input type="number" step="0.01" wire:model.lazy="rows.{{ $i }}.sales_target"
                                       class="w-36 rounded-lg border border-gray-300 px-2 py-1 text-xs bg-white focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none"
                                       @disabled($r['locked'])>
                            </td>

                            {{-- Override --}}
                            <td class="px-2 py-1">
                                <input type="number" step="0.01" wire:model.lazy="rows.{{ $i }}.override_rate"
                                       class="w-28 rounded-lg border border-gray-300 px-2 py-1 text-xs bg-white focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none"
                                       @disabled($r['locked'])>
                            </td>

                            {{-- قفل --}}
                            <td class="px-2 py-1 text-center">
                                <button wire:click="toggleLock({{ $r['user_id'] }})"
                                        class="px-3 py-1 rounded-xl text-xs font-bold shadow transition
                                               {{ $r['locked'] ? 'bg-gray-700 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' }}">
                                    {{ $r['locked'] ? 'مقفول' : 'مفتوح' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-400">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ليست صفحة مُرقّمة هنا، لذا لا نعرض روابط صفحات --}}
    </div>
</div>
