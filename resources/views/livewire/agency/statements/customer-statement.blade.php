@php $currency = Auth::user()->agency->currency ?? 'USD'; @endphp

<div class="space-y-6">
 <div class="flex justify-between items-center">
  <h2 class="text-2xl font-bold text-[rgb(var(--primary-700))]">
    كشف حساب: {{ $customer->name }}
  </h2>

  <!-- زر الرجوع + التصدير بدون مسافة -->
  <div class="inline-flex items-center gap-0">

  
    @if (!empty($selectedRows))
      <x-primary-button type="button" wire:click="exportPdfAuto"
          class="flex items-center gap-2">
        تصدير PDF
      </x-primary-button>
    @endif

     <a href="{{ route('agency.customer-detailed-invoices') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg border transition text-sm font-medium
                      bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))] hover:shadow-md hover:text-[rgb(var(--primary-700))]">
                <svg class="h-5 w-5 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>رجوع</span>
            </a>

  </div>
</div>


  <!-- فلاتر -->
  <div class="bg-white rounded-xl shadow p-4">
    <div class="grid md:grid-cols-4 gap-4 items-end">
      <x-input-field name="beneficiary" label="بحث باسم المستفيد" wireModel="beneficiary" placeholder="مثال: أمير علي" />
      <x-date-picker name="fromDate" label="من تاريخ" wireModel="fromDate" />
      <x-date-picker name="toDate"   label="إلى تاريخ"  wireModel="toDate" />
      <button type="button" wire:click="$set('beneficiary','');$set('fromDate','');$set('toDate','')"
              class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
        مسح الفلاتر
      </button>
    </div>
  </div>

  <!-- جدول كشف الحساب -->
  <div class="bg-white rounded-xl shadow p-4 overflow-x-auto">
    <table class="min-w-full divide-y text-sm text-right">
      <thead class="bg-gray-100 text-gray-700">
        <tr>
        <th class="px-3 py-2 text-center" x-data="{ sel: @entangle('selectedRows') }">
          <input type="checkbox"
                x-ref="selAll"
                @click.prevent="$wire.toggleSelectAll()"
                x-effect="
                  const total = {{ count($statement) }};
                  const n = Array.isArray(sel) ? sel.length : 0;
                  $refs.selAll.checked = total>0 && n===total;
                  $refs.selAll.indeterminate = n>0 && n<total;
                "
                style="accent-color: rgb(var(--primary-600));"
                title="تحديد/إلغاء الكل">
        </th>



          <th class="px-3 py-2">رقم</th>
          <th class="px-3 py-2">تاريخ الخدمة</th>
          <th class="px-3 py-2">الوصف</th>
          <th class="px-3 py-2">عليه</th>
          <th class="px-3 py-2">له</th>
          <th class="px-3 py-2">الرصيد</th>
        </tr>
      </thead>
      <tbody class="divide-y">
      @forelse($statement as $i => $line)
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 text-center">
                <input type="checkbox" wire:model.live="selectedRows" value="{{ $i }}">

                </td>
                <td class="px-3 py-2">{{ $line['no'] }}</td>
                <td class="px-3 py-2">{{ $line['date'] }}</td>
                <td class="px-3 py-2">{{ $line['desc'] }}</td>
                <td class="px-3 py-2 text-blue-700">{{ number_format($line['debit'], 2) }} {{ $currency }}</td>
                <td class="px-3 py-2 text-green-700">{{ number_format($line['credit'], 2) }} {{ $currency }}</td>
                <td class="px-3 py-2 font-semibold">{{ number_format($line['balance'], 2) }} {{ $currency }}</td>
            </tr>
            @empty

          <tr><td colspan="7" class="text-center text-gray-400 py-6">لا توجد عمليات ضمن النطاق.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
