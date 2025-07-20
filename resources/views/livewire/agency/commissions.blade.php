@php
use App\Tables\EmployeeCommissionsTable;
use App\Tables\CustomerCommissionsTable;

$employeeColumns = EmployeeCommissionsTable::columns();
$customerColumns = CustomerCommissionsTable::columns();
@endphp

<div>
    <div class="space-y-6">
        <!-- رسائل النظام -->
        @if(session()->has('message'))
            <div x-data="{ show: true }"
                 x-init="setTimeout(() => show = false, 2000)"
                 x-show="show"
                 x-transition
                 class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm" 
                 style="background-color: rgb(var(--primary-500));">
                {{ session('message') }}
            </div>
        @endif

        <h2 class="text-xl font-bold text-[rgb(var(--primary-700))] border-b pb-2">
            تقرير العمولات للموظفين لشهر {{ $month }}/{{ $year }}
        </h2>

        <x-data-table :rows="$employeeCommissions" :columns="$employeeColumns" />

        @if(count($customerCommissions) > 0)
            <h2 class="text-xl font-bold text-[rgb(var(--primary-700))] border-b pb-2 mt-10">
                عمولات العملاء {{ $month }}/{{ $year }}
            </h2>
            
            <x-data-table :rows="$customerCommissions" :columns="$customerColumns" />
        @endif
    </div>
</div>