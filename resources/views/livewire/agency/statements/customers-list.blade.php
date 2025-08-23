@php
    use App\Tables\CustomerStatementTable;
    $columns = CustomerStatementTable::columns();
@endphp

<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md p-5">

        <h2 class="text-2xl font-bold text-[rgb(var(--primary-700))] mb-4">
            العملاء
        </h2>

        <x-input-field
            name="search"
            label="ابحث باسم العميل"
            placeholder="ابحث باسم العميل..."
            wireModel="search"
            width="w-full"
            containerClass="relative mt-1 mb-4 w-full"
        />

       <x-data-table
            :columns="$columns"
            :rows="$customers"
            wire:key="customers-{{ md5($search) }}-p{{ $customers->currentPage() }}"
        />

    </div>
</div>
