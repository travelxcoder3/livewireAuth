<div>
 @php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp

<div class="space-y-6">

    <!-- العنوان + رجوع + التصدير -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            تقرير عروض الأسعار
        </h2>

        <div class="relative flex items-center gap-2" x-data="{ open:false }" x-cloak wire:ignore>
    <button @click="open = !open"
            class="flex items-center gap-2 text-white font-bold px-4 py-2 rounded-xl shadow-md transition text-sm hover:shadow-lg"
            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        <i class="fas fa-file-export"></i>
        <span>تصدير التقارير </span>
        <i class="fas fa-chevron-down text-xs" :class="{ 'rotate-180': open }"></i>
    </button>

    <div x-show="open" @click.outside="open=false" x-transition
         class="absolute right-0 top-full mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
        <button wire:click="exportToExcel"
                class="block w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
            <i class="fas fa-file-excel text-green-500 mr-2"></i> Excel
        </button>
        <a href="{{ route('agency.reports.quotations.pdf', request()->query()) }}" target="_blank"
           class="block w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
            <i class="fas fa-file-pdf text-red-500 mr-2"></i> PDF
        </a>
    </div>
</div>

    </div>

    </div>

    <!-- شريط الفلاتر بنفس أسلوب الصفحة الثانية -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4 items-end">
            <x-date-picker name="startDate" label="من تاريخ" wireModel="startDate" />
            <x-date-picker name="endDate"   label="إلى تاريخ" wireModel="endDate" />
            <x-select-field
                    name="userId"
                    label="المستخدم"
                    wireModel="userId"
                    :options="$users->pluck('name','id')->toArray()"
                    placeholder="جميع المستخدمين"
                    errorName="userId"
                />
            <div class="mt-3 flex justify-between">
                <div></div>
               

            <button type="button" wire:click="resetFilters" 
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm flex items-center gap-2 w-full sm:w-auto">
                اعاده تعيين الفلاتر 
            </button>

            </div>
        </div>
    </div>

    <!-- الجدول بنفس الستايل -->
    <div class="overflow-x-auto rounded-xl shadow bg-white">
      <x-data-table :rows="$rows" :columns="$columns" />
        <div class="px-4 py-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>
</div>
