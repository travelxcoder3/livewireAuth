
@php
    use App\Services\ThemeService;
    use App\Tables\CollectionTable;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);

    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
    $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
    $containerClass = 'relative mt-1';

    $columns = CollectionTable::columns();
    // تجهيز البيانات مع القيم المحسوبة
    $rows = $sales->map(function($sale, $i) {
        $totalInvoice = $sale->usd_sell ?? 0;
        $paidFromSales = $sale->amount_received ?? 0;
        $paidFromCollections = $sale->collections->sum('amount');
        $paidTotal = $paidFromSales + $paidFromCollections;
        $remaining = $totalInvoice - $paidTotal;
        $debtAge = $sale->sale_date ? round(abs(now()->diffInDays($sale->sale_date, false))) : null;
        return (object) [
            'id' => $sale->id,
            'index' => $i + 1,
            'beneficiary_name' => $sale->beneficiary_name ?? '-',
            'remaining' => $remaining,
            'last_payment' => optional($sale->collections->last())->payment_date ?? '-',
            'debt_age' => $debtAge ? $debtAge . ' يوم' : '-',
            'customer_type' => optional($sale->collections->last()?->customerType)->label ?? '-',
            'debt_type' => optional($sale->collections->last()?->debtType)->label ?? '-',
            'customer_response' => optional($sale->collections->last()?->customerResponse)->label ?? '-',
            'customer_relation' => optional($sale->collections->last()?->customerRelation)->label ?? '-',
        ];
    });
    // معالجة url في actions
    foreach ($columns as &$col) {
        if (isset($col['actions'])) {
            foreach ($col['actions'] as &$action) {
                if (isset($action['url']) && is_callable($action['url'])) {
                    foreach ($rows as $row) {
                        $row->{$action['type'].'_url'} = $action['url']($row);
                    }
                    $action['url'] = null;
                }
            }
        }
    }
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
    <x-data-table :rows="$rows" :columns="$columns" />

        @if($sales->hasPages())
            <div class="px-4 py-2 border-t border-gray-200">
                {{ $sales->links() }}
            </div>
        @endif
</div>
