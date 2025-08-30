@php
    use App\Tables\EmployeeStatementTable;
    $columns = EmployeeStatementTable::columns();
    $accountTypeOptions = $accountTypeOptions ?? [];
@endphp

<div class="space-y-6">
  <div class="bg-white rounded-xl shadow-md p-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
      <h2 class="text-2xl font-bold text-[rgb(var(--primary-700))]">كشف حساب الموظفين</h2>

      <div class="flex gap-3 mt-3 sm:mt-0">
        <div class="px-4 py-2 bg-green-50 border border-green-300 rounded-lg shadow-sm flex items-center gap-2">
          <span class="text-xs text-gray-600">لصالح الموظفين</span>
          <span class="text-sm font-bold text-green-600">{{ number_format($totalRemainingForEmployee, 2) }}</span>
        </div>
        <div class="px-4 py-2 bg-red-50 border border-red-300 rounded-lg shadow-sm flex items-center gap-2">
          <span class="text-xs text-gray-600">على الموظفين</span>
          <span class="text-sm font-bold text-red-600">{{ number_format($totalRemainingForCompany, 2) }}</span>
        </div>
      </div>
    </div>

    <div class="grid md:grid-cols-12 gap-4 items-center mb-3">
      <div class="md:col-span-3">
        <x-input-field name="search" label="ابحث باسم الموظف" placeholder="ابحث باسم الموظف..."
                       wireModel="search" width="w-full" containerClass="relative mt-1 w-full"/>
      </div>

      <div class="md:col-span-3">
        <x-select-field label="نوع الحساب" :options="$accountTypeOptions" wireModel="accountType"
                        placeholder="الكل" containerClass="relative mt-1 w-full"/>
      </div>

      <div class="md:col-span-3">
        <x-date-picker name="fromDate" label="من تاريخ (آخر حركة)" wireModel="fromDate"/>
      </div>

      <div class="md:col-span-3">
        <x-date-picker name="toDate" label="إلى تاريخ (آخر حركة)" wireModel="toDate"/>
      </div>

      <div class="md:col-span-12 flex justify-end">
        <button type="button" wire:click="resetFilters"
                class="w-34 h-9 rounded-lg shadow text-sm font-medium
                       bg-gray-200 hover:bg-gray-300 text-gray-800
                       flex items-center justify-center gap-2 transition">
          إعادة تعيين الفلاتر
        </button>
      </div>
    </div>

    {{-- الجدول باستخدام نفس الـ component --}}
    <x-data-table
        :columns="$columns"
        :rows="$employees"
        wire:key="employees-{{ md5($search.'|'.$accountType.'|'.$fromDate.'|'.$toDate) }}-p{{ $employees->currentPage() }}"
    />
  </div>
</div>
