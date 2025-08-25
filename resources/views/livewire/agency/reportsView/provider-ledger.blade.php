@php
    use App\Services\ThemeService;
    use App\Tables\ProviderLedgerTable;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';

    $columns = ProviderLedgerTable::columns();
@endphp


<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: .5rem;">
            كشف حساب المزوّدين
        </h2>

    </div>

    {{-- فلاتر بسطر واحد --}}
    <div class="bg-white rounded-xl shadow-md p-4 space-y-3">
        <div class="grid md:grid-cols-4 items-end gap-3">
            <x-input-field
                name="provider_name"
                label="اسم المزوّد"
                wireModel="providerName"
                placeholder="ابحث عن اسم المزوّد"
                containerClass="relative"
                fieldClass="{{ $fieldClass }}"
            />

            <x-date-picker
                name="from_date"
                label="من تاريخ"
                placeholder="اختر التاريخ"
                wireModel="fromDate"
            />

            <x-date-picker
                name="to_date"
                label="إلى تاريخ"
                placeholder="اختر التاريخ"
                wireModel="toDate"
            />

            <div class="flex justify-end">
            
                 <button wire:click="resetFilters"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
                    إعادة تعيين
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <x-data-table :rows="$rows" :columns="$columns" />
        </div>
    </div>
</div>

