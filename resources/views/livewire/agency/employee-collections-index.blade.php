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

  <div class="bg-white rounded-xl shadow p-4 grid md:grid-cols-4 gap-3 text-sm">
    <x-input-field wireModel="name" label="اسم الموظف" placeholder="ابحث بالاسم" />
    <x-input-field wireModel="from" label="من تاريخ آخر تحصيل" type="date" />
    <x-input-field wireModel="to"   label="إلى تاريخ آخر تحصيل" type="date" />
    <button wire:click="$set('name','');$set('from',null);$set('to',null)"
            class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded">مسح الفلاتر</button>
  </div>

  <div class="bg-white rounded-xl shadow">
    <div class="overflow-x-auto">
      <div class="min-w-[1000px] md:min-w-0">
        <x-data-table :rows="$rows" :columns="$columns" />
      </div>
    </div>
  </div>
</div>
