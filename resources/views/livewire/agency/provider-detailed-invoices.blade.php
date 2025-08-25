@php
    use App\Tables\ProviderInvoiceTable;
    $columns = ProviderInvoiceTable::columns();
@endphp

<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md p-5">
        <h2 class="text-2xl font-bold text-[rgb(var(--primary-700))] mb-4">فواتير المزوّدين</h2>

        <x-input-field
            name="search"
            label="ابحث باسم المزوّد"
            placeholder="ابحث باسم المزوّد..."
            wireModel="search"
            width="w-full"
            containerClass="relative mt-1 mb-4 w-full"
        />

        <x-data-table
            :columns="$columns"
            :rows="$providers"
            wire:key="providers-{{ md5($search) }}-p{{ $providers->currentPage() }}"
        />
    </div>
</div>
