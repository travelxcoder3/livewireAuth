<div class="space-y-6">
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show" x-transition
            class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
            style="background-color: rgb(var(--primary-500));">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold"
                style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
                ملف الموظفين
            </h2>

        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-3">
            <div class="relative mt-1">
                <input type="text" wire:model.live="search"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer"
                    placeholder=" ">
                <label
                    class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
                    ابحث بالاسم أو البريد
                </label>
            </div>

            <x-select-field label="القسم" :options="$departments" wireModel="department_filter" />
            <x-select-field label="الوظيفة" :options="$positions" wireModel="position_filter" />

        </div>

        <div class="flex justify-end">
            <button type="button" wire:click="resetFilters"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm w-full sm:w-auto">
                اعادة تعيين الفلاتر
            </button>
        </div>
    </div>

    @php
        use App\Tables\EmployeeFileTable;
        $columns = EmployeeFileTable::columns();
    @endphp

    @if($showWallet && $walletUserId)
        <livewire:agency.employee-wallet :userId="$walletUserId" wire:key="emp-wallet-{{ $walletUserId }}" />
    @endif

    <x-data-table :rows="$employees" :columns="$columns" />

  
</div>
