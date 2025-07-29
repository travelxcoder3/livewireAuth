@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use App\Tables\SalesTable;
    $columns = SalesTable::customerFollowUpColumns();
    // إعداد ألوان الثيم
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);

    // كلاس الحقل والفلاتر
    $fieldClass =
        'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
    $labelClass =
        'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
@endphp

<div class="space-y-6">
    <!-- عنوان الصفحة -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            تقرير تتبع العملاء
        </h2>
        <!-- زر قائمة تصدير التقرير -->
        <div wire:ignore x-data="{ open: false }" class="relative">
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
                <!-- رابط PDF -->
                <a href="{{ route('agency.reports.customers-follow-up.pdf', request()->query()) }}" target="_blank"
                    class="block w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                    PDF
                </a>
            </div>
        </div>
    </div>

    <!-- فلاتر التقرير -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- نوع الخدمة -->
            <x-select-field label="نوع الخدمة" name="service_type" wireModel="serviceTypeFilter" :options="$serviceTypes->pluck('label', 'id')->toArray()"
                placeholder="جميع أنواع الخدمات" containerClass="relative" />

            <!-- تاريخ الخدمة -->
            <div class="relative mt-1">
                <input type="date" id="service_date" name="service_date" wire:model="serviceDate"
                    wire:change="$refresh" placeholder=" " class="peer {{ $fieldClass }}" />
                <label for="service_date" class="{{ $labelClass }}">تاريخ الخدمة</label>
            </div>
        </div>

        <!-- زر إعادة تعيين الفلاتر -->
        <div class="flex justify-end mt-4">
            <button wire:click="resetFilters"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                إعادة تعيين الفلاتر
            </button>
        </div>
    </div>

    <!-- جدول النتائج -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <x-data-table :columns="$columns" :rows="$sales" />
    </div>

    <!-- رسائل النظام -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show" x-transition
            class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
            style="background-color: rgb(var(--primary-500));">
            {{ session('message') }}
        </div>
    @endif
</div>
