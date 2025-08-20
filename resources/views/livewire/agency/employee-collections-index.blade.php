@php
  use App\Tables\EmployeeCollectionsTable;
  $columns = EmployeeCollectionsTable::columns();
  foreach ($columns as &$c) {
    if (isset($c['actions'])) {
      foreach ($c['actions'] as &$a) {
        if (isset($a['url']) && is_callable($a['url'])) {
          foreach ($rows as $row) { $row->{$a['type'].'_url'} = $a['url']($row); }
          $a['url'] = null;
        }
      }
    }
  }
@endphp

<div class="space-y-6">
  <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700));">تحصيلات الموظفين</h2>

 <div class="bg-white rounded-xl shadow p-4 grid md:grid-cols-4 gap-3 text-sm items-end relative">

  <x-input-field wireModel="name" label="اسم الموظف" placeholder="ابحث بالاسم" />

  <x-date-picker
      name="from"
      wireModel="from"
      label="من تاريخ آخر تحصيل"
      placeholder="اختر التاريخ" />

  <x-date-picker
      name="to"
      wireModel="to"
      label="إلى تاريخ آخر تحصيل"
      placeholder="اختر التاريخ" />

  <!-- زر مسح الفلاتر بمحاذاة اليسار -->
  <div class="absolute top-4 left-4">
    <button
        wire:click="resetFilters"
        class="w-34 h-9 rounded-lg shadow text-sm font-medium
               bg-gray-200 hover:bg-gray-300 text-gray-800
               flex items-center justify-center gap-2 transition">
        اعادة تعيين الفلاتر
    </button>
  </div>

</div>


  <div class="bg-white rounded-xl shadow">
    <div class="overflow-x-auto">
      <div class="min-w-[1000px] md:min-w-0">
        <x-data-table :rows="$rows" :columns="$columns" />
      </div>
    </div>
  </div>
</div>
