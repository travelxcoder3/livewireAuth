@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
@endphp

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: .5rem;">
            التقرير المالي لحسابات المزوّدين
        </h2>

        <div class="flex flex-wrap gap-2">
            <div class="flex items-center gap-2 bg-white border border-green-300 rounded-lg px-3 py-1.5 shadow-sm text-xs">
                <div class="bg-green-100 text-green-600 rounded-full p-1.5 text-sm">✅</div>
                <div class="leading-tight">
                    <div class="text-gray-600">إجمالي الرصيد <strong>لصالح المزوّدين</strong></div>
                    <div class="text-green-700 font-bold font-mono text-sm">{{ number_format($totalForProviders,2) }}</div>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-white border border-red-300 rounded-lg px-3 py-1.5 shadow-sm text-xs">
                <div class="bg-red-100 text-red-600 rounded-full p-1.5 text-sm">🔴</div>
                <div class="leading-tight">
                    <div class="text-gray-600">إجمالي الرصيد <strong>لصالح الوكالة</strong></div>
                    <div class="text-red-700 font-bold font-mono text-sm">{{ number_format($totalForAgency,2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- فلاتر -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block mb-1 text-sm font-medium" style="color: rgb(var(--primary-700));">اسم المزوّد</label>
                <input type="text" wire:model.live="providerName" placeholder="ابحث عن اسم المزوّد" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium" style="color: rgb(var(--primary-700));">من تاريخ</label>
                <input type="date" wire:model.live="fromDate" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium" style="color: rgb(var(--primary-700));">إلى تاريخ</label>
                <input type="date" wire:model.live="toDate" class="{{ $fieldClass }}">
            </div>
            <div class="flex items-end">
                <button wire:click="resetFilters"
                    class="text-white font-bold px-4 py-2 rounded-xl shadow text-sm"
                    style="background-color: rgb(var(--primary-500));"
                    onmouseover="this.style.backgroundColor='rgb(var(--primary-600))'"
                    onmouseout="this.style.backgroundColor='rgb(var(--primary-500))'">إعادة تعيين</button>
            </div>
        </div>
    </div>

    <!-- الجدول -->
    <div class="bg-white rounded-xl shadow-md overflow-auto">
        <table class="w-full text-sm text-center table-auto border border-gray-200 rounded-lg overflow-hidden">
            <thead>
    <tr class="bg-[rgb(var(--primary-700))] text-gray">
        <th class="py-3 px-4 border-b">اسم المزوّد</th>
        <th class="py-3 px-4 border-b">صافي الشراء</th>
        <th class="py-3 px-4 border-b" colspan="2">الرصيد</th>
        <th class="py-3 px-4 border-b">إجمالي الشراء</th>
        <th class="py-3 px-4 border-b">استرداد</th>
        <th class="py-3 px-4 border-b">إلغاء</th>
        <th class="py-3 px-4 border-b">آخر عملية</th>
        <th class="py-3 px-4 border-b">الإجراء</th>
    </tr>
    <tr class="bg-gray-50 text-xs text-gray-600">
        <th></th><th></th>
        <th class="py-1 px-4 border-b">له</th>
        <th class="py-1 px-4 border-b">عليه</th>
        <th colspan="4"></th>
    </tr>
</thead>
<tbody class="divide-y">
@foreach($providers as $p)
<tr class="hover:bg-gray-50">
    <td class="py-2 px-4 text-right font-medium">{{ $p['name'] }}</td>
    <td class="py-2 px-4 font-mono">{{ number_format($p['net'],2) }}</td>
    <td class="py-2 px-4 text-green-600 font-mono">{{ number_format($p['for_provider'],2) }}</td>
    <td class="py-2 px-4 text-red-600 font-mono">{{ number_format($p['for_agency'],2) }}</td>
    <td class="py-2 px-4 font-mono">{{ number_format($p['buy'],2) }}</td>
    <td class="py-2 px-4 text-rose-700 font-mono">{{ number_format($p['refund'],2) }}</td>
    <td class="py-2 px-4 text-rose-700 font-mono">{{ number_format($p['cancel'],2) }}</td>
    <td class="py-2 px-4">
        {{ $p['last_sale_date'] ? \Carbon\Carbon::parse($p['last_sale_date'])->format('Y-m-d') : '-' }}
    </td>
    <td class="py-2 px-4">
        <a href="{{ route('agency.reports.provider-accounts.details', $p['id']) }}"
           class="bg-white border border-[rgb(var(--primary-500))] text-[rgb(var(--primary-500))] px-3 py-1 rounded hover:shadow-md">
           تفاصيل
        </a>
    </td>
</tr>
@endforeach
</tbody>

        </table>
    </div>
</div>
