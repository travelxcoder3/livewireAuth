@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);

    $fieldClass =
        'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
    $labelClass =
        'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
    $containerClass = 'relative mt-1';
@endphp

<div class="space-y-6">
    <!-- عنوان الصفحة وأدوات التقرير -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            تقرير الحسابات
        </h2>

        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-semibold text-gray-700">الإجمالي:</label>
                <input type="text" value="{{ number_format($totalSales, 2) }}" readonly
                    class="bg-gray-100 border border-gray-300 rounded px-3 py-1 text-sm text-gray-700 w-32 text-center">
            </div>
            @php
                // نستخدم JS جانب العميل لالتقاط الـ URL الكامل مع الفلاتر
                // ثم نستبدل الجزء "/accounts" بـ "/accounts/pdf"
                $placeholder = ''; // يبقى فارغ لأننا سنحسب pdfUrl في Alpine
            @endphp

            <!-- قائمة منسدلة للتصدير بعد التعديل -->
            <div wire:ignore.self x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                    class="flex items-center gap-2 text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                    <i class="fas fa-file-export"></i>
                    <span>تصدير التقرير</span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                        :class="{ 'transform rotate-180': open }"></i>
                </button>

                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                    <!-- زر Excel -->
                    <button wire:click="exportToExcel"
                        class="block w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-file-excel text-green-500 mr-2"></i>
                        Excel
                    </button>

                    <!-- زر PDF يحسب الرابط من window.location في كل نقرة -->
                    <a href="#"
                        @click.prevent="
                const url = window.location.href.replace(
                    '/agency/reports/accounts',
                    '/agency/reports/accounts/pdf'
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
                containerClass="relative" fieldClass="{{ $fieldClass }}" />

            <x-select-field label="نوع الخدمة" name="service_type" wireModel="serviceTypeFilter" :options="$serviceTypes->pluck('label', 'id')->toArray()"
                placeholder="جميع أنواع الخدمات" containerClass="relative" />

            <x-select-field label="المزود" name="provider" wireModel="providerFilter" :options="$providers->pluck('name', 'id')->toArray()"
                placeholder="جميع المزودين" containerClass="relative" />

            <x-select-field label="الحساب" name="account" wireModel="accountFilter" :options="$customers->pluck('name', 'id')->toArray()"
                placeholder="جميع الحسابات" containerClass="relative" />

            <x-input-field name="start_date" label="من تاريخ" wireModel="startDate" type="date"
                containerClass="relative" fieldClass="{{ $fieldClass }}" />

            <x-input-field name="end_date" label="إلى تاريخ" wireModel="endDate" type="date"
                containerClass="relative" fieldClass="{{ $fieldClass }}" />
        </div>

        <div class="flex justify-end mt-4">
            <button wire:click="resetFilters"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                إعادة تعيين الفلاتر
            </button>
        </div>
    </div>

    <!-- جدول البيانات -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <x-data-table :rows="$sales" :columns="$columns" />
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
