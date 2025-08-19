<!-- Drawer جانبي ثابت -->
<!-- Drawer جانبي ثابت -->
<div class="fixed inset-0 z-50 flex justify-end">
  <!-- overlay يغطي الشاشة كاملة -->
  <div class="absolute inset-0 z-40 bg-black/30" wire:click="$dispatch('wallet-closed')"></div>

  <!-- اللوحة -->
  <div class="relative z-50 w-full sm:w-[520px] bg-white h-screen shadow-2xl p-5 overflow-y-auto">

     <button type="button"
        wire:click="$dispatch('wallet-closed')"
        aria-label="إغلاق"
        class="absolute top-2 left-2 p-1 rounded-full text-gray-500 hover:text-red-600 hover:bg-gray-100">
  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
  </svg>
</button>

    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-gray-500">عميل</div>
        <h3 class="text-xl font-bold">{{ $customer->name }}</h3>
      </div>
      <div class="text-right">
        <div class="text-xs text-gray-500">الرصيد الحالي</div>
        <div class="text-2xl font-extrabold">
          {{ number_format($wallet->balance,2) }}
          <span class="text-sm text-gray-500">{{ $customer->agency?->currency }}</span>
        </div>
        <div class="flex gap-2">

</div>

      </div>
    </div>

    <hr class="my-4"/>

    <!-- نموذج عملية جديدة -->
    <div class="grid gap-3">
     <x-select-field
        label=""
        name="type"
        wireModel="type"
        :options="[
            'deposit'  => 'إيداع',
            'withdraw' => 'سحب',
        ]"
        placeholder="اختر العملية"
        />


  {{-- المبلغ --}}
  <x-input-field
      name="amount"
      wireModel="amount"
      label="المبلغ"
      placeholder="المبلغ"
      type="number" />

  {{-- مرجع العملية --}}
  <x-input-field
      name="reference"
      wireModel="reference"
      label="مرجع العملية (اختياري)"
      placeholder="مرجع العملية (اختياري)" />
  <div class="relative mt-1">
    <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">ملاحظة (اختياري)</label>
    <textarea wire:model="note" placeholder=" "
              class="peer w-full rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm text-gray-600
                     focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))]
                     focus:border-[rgb(var(--primary-500))] transition duration-200"></textarea>
  </div>


      <x-primary-button wire:click="submit">
  تنفيذ
</x-primary-button>


      @if (session()->has('message'))
        <div class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded p-2">
          {{ session('message') }}
        </div>
      @endif
    </div>

    <hr class="my-4"/>

  <!-- فلاتر كشف الحساب -->
  <div class="grid grid-cols-2 gap-2 text-sm">
    <x-date-picker
        name="from"
        wireModel="from"
        label="من تاريخ"
        placeholder="اختر التاريخ" />

    <x-date-picker
        name="to"
        wireModel="to"
        label="إلى تاريخ"
        placeholder="اختر التاريخ" />

    <div class="col-span-2">
      <x-input-field
          name="q"
          wireModel="q"
          label="بحث في المرجع/الملاحظة/المنفذ"
          placeholder="بحث في المرجع/الملاحظة/المنفذ" />
    </div>
  </div>


    <!-- جدول الحركات -->
    <div class="mt-3 overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 text-right">التاريخ</th>
            <th class="p-2 text-right">النوع</th>
            <th class="p-2 text-right">المبلغ</th>
            <th class="p-2 text-right">الرصيد</th>
            <th class="p-2 text-right">مرجع</th>
            <th class="p-2 text-right">منفّذ</th>
          </tr>
        </thead>
        <tbody>
          @foreach($this->transactions as $t)
            <tr class="{{ $t->type === 'deposit' ? 'bg-emerald-50' : ($t->type === 'withdraw' ? 'bg-red-50' : '') }}">
              <td class="p-2">{{ $t->created_at }}</td>
              <td class="p-2">{{ $t->type }}</td>
              <td class="p-2 text-right">{{ number_format($t->amount,2) }}</td>
              <td class="p-2 text-right">{{ number_format($t->running_balance,2) }}</td>
              <td class="p-2">{{ $t->reference }}</td>
              <td class="p-2">{{ $t->performed_by_name }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div class="mt-2">
        {{ $this->transactions->links() }}
      </div>
    </div>

   <button wire:click="$dispatch('wallet-closed')"
        class="mt-6 w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl">
  إغلاق
</button>
  </div>
</div>

<script>
  window.addEventListener('closeWalletFromChild', () => {
    @this.dispatch('closeWalletFromParent');
  });
</script>
