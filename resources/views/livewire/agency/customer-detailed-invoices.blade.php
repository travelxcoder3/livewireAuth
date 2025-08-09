@php
    use App\Tables\CustomerInvoiceTable;
    $columns = CustomerInvoiceTable::columns();
@endphp

<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md p-5">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-[rgb(var(--primary-700))]">
                فواتير العملاء
            </h2>

            <div class="w-1/3">
                <input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="ابحث باسم العميل..."
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring focus:ring-blue-100" />
            </div>
        </div>

        <x-data-table
            :columns="$columns"
            :rows="$customers"
            wire:key="customers-{{ md5($search) }}-p{{ $customers->currentPage() }}"
        />
    </div>
</div>
