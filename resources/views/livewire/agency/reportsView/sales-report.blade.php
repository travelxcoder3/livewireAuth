@php
    use App\Services\ThemeService;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);

    $fieldClass =
        'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
@endphp

<div class="space-y-6">
    <!-- عنوان الصفحة وأدوات التقرير -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            تقرير المبيعات
        </h2>

        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-semibold text-gray-700">الإجمالي:</label>
                <input type="text" value="{{ number_format($totalSales, 2) }}" readonly
                    class="bg-gray-100 border border-gray-300 rounded px-3 py-1 text-sm text-gray-700 w-32 text-center">
            </div>

            <div wire:ignore.self x-data="{ open: false }" class="relative">
                <x-primary-button type="button" @click="open = !open" padding="px-4 py-2"
                    class="flex items-center gap-2 hover:shadow-lg">
                    <i class="fas fa-file-export"></i>
                    <span>تصدير التقرير</span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                        :class="{ 'transform rotate-180': open }"></i>
                </x-primary-button>


                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                    <!-- Excel -->
                    <button wire:click="exportToExcel"
                        class="block w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-file-excel text-green-500 mr-2"></i>
                        Excel
                    </button>

                    <!-- PDF -->
                    <a href="#"
                        @click.prevent="
                            const url = window.location.href.replace(
                              '/agency/reports/sales',
                              '/agency/reports/sales/pdf'
                            );
                            window.open(url, '_blank');
                        "
                        class="block w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                        PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- فلاتر التقرير -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4">
            <x-input-field name="search" label="بحث عام" wireModel="search" placeholder="ابحث في جميع الحقول..."
                fieldClass="{{ $fieldClass }}" />

            <x-select-field label="نوع الخدمة" name="service_type" wireModel="serviceTypeFilter" :options="$serviceTypes->pluck('label', 'id')->toArray()"
                placeholder="جميع أنواع الخدمات" />

            <x-select-field label="المزود" name="provider" wireModel="providerFilter" :options="$providers->pluck('name', 'id')->toArray()"
                placeholder="جميع المزودين" />

            <x-select-field label="الحساب" name="customers" wireModel="accountFilter" :options="$customers->pluck('name', 'id')->toArray()"
                placeholder="جميع الحسابات" />

            <div class="flex flex-col gap-1">
                <label for="start_date" class="text-sm font-semibold text-gray-600">من تاريخ</label>
                <input type="date" name="start_date" wire:change="$refresh" wire:model="startDate"
                    class="{{ $fieldClass }}" />

            </div>

            <div class="flex flex-col gap-1">
                <label for="end_date" class="text-sm font-semibold text-gray-600">إلى تاريخ</label>
                <input type="date" name="end_date" wire:change="$refresh" wire:model="endDate"
                    class="{{ $fieldClass }}" />
            </div>

        </div>

        <div class="flex justify-end mt-4">
            <button wire:click="resetFilters"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
                إعادة تعيين الفلاتر
            </button>
        </div>
    </div>

    <!-- جدول البيانات باستخدام المكون الموحد -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <x-data-table :columns="$columns" :rows="$sales" />
    </div>

    <!-- Pagination -->
    @if ($sales->hasPages())
        <div class="px-4 py-2 bg-white rounded-b-xl shadow-md border-t border-gray-200">
            {{ $sales->links() }}
        </div>
    @endif

    <!-- رسائل النظام -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show" x-transition
            class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
            style="background-color: rgb(var(--primary-500));">
            {{ session('message') }}
        </div>
    @endif
</div>
