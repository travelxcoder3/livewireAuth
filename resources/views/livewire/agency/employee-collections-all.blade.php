
@php
    use App\Services\ThemeService;
    use App\Tables\CollectionTableEmployee;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);

    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
    $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
    $containerClass = 'relative mt-1';

    $columns = CollectionTableEmployee::columns();
    // تجهيز البيانات مع القيم المحسوبة
$rows = $sales->map(function($customer, $i) {
    $customer->index = $i + 1;
    return $customer;
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
            <input type="text" wire:model.live="search" class="{{ $fieldClass }}" placeholder="اسم العميل ">
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

        <div class="col-span-1 md:col-span-4 mt-2">
  <!-- موبايل: شبكة 2x2 -->
  <div class="md:hidden grid grid-cols-2 gap-2 w-full">
    @can('collection.details.view')
    <x-primary-button href="{{ route('agency.collections.all') }}" class="w-full text-xs" gradient>
      إظهار جميع التحصيلات
    </x-primary-button>
    <x-primary-button href="{{ route('agency.customer-credit-balances') }}" class="w-full text-xs" gradient>
      إظهار جميع المديونية
    </x-primary-button>
    <x-primary-button href="{{ route('agency.customer-accounts') }}" class="w-full text-xs col-span-2" gradient>
      عرض جميع حسابات العملاء
    </x-primary-button>
    @endcan
    <button wire:click="resetFilters"
      class="w-full text-xs bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded shadow col-span-2">
      إعادة تعيين الفلاتر
    </button>
  </div>


  <!-- ديسكتوب/تابلت: صف يمين -->
  <div class="hidden md:flex justify-end flex-wrap gap-2">
    @can('collection.details.view')
    <x-primary-button href="{{ route('agency.collections.all') }}" class="text-sm" gradient>
        حركة العمليات
    </x-primary-button>
    <x-primary-button href="{{ route('agency.customer-credit-balances') }}" class="text-sm" gradient>
      عمليات الاسترجاع   
    </x-primary-button>
    <x-primary-button href="{{ route('agency.customer-accounts') }}" class="text-sm" gradient>
      عرض عمليات العملاء
    </x-primary-button>
    @endcan
    <button wire:click="resetFilters"
      class="text-sm bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded shadow">
      إعادة تعيين الفلاتر
    </button>
  </div>
</div>


<!-- جدول التحصيلات -->
<div class="col-span-1 md:col-span-4 mt-4">
  <div class="bg-white rounded-xl shadow-md">
    <div class="overflow-x-auto w-full">
      <div class="inline-block min-w-full align-middle">
        <!-- امنع الانضغاط على الموبايل ثم اسمح بالتمدد على الشاشات الأكبر -->
        <div class="min-w-[1100px] md:min-w-0">
          <x-data-table :rows="$rows" :columns="$columns" />
        </div>
      </div>
    </div>
  </div>
</div>
     
</div>
