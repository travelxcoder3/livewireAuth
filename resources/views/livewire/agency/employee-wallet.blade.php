<div class="fixed inset-0 z-[70] flex justify-end">
  <div class="absolute inset-0 z-[60] bg-black/30" wire:click="$dispatch('wallet-closed')"></div>

  <div class="relative z-[70] w-full sm:w-[520px] bg-white h-screen p-5 overflow-y-auto">
    <div class="flex items-start justify-between">
      <h3 class="text-xl font-bold">حساب الموظف: {{ $user->name }}</h3>

      <div class="text-right">
        <div class="text-xs text-gray-500">الرصيد الحالي</div>
        <div class="text-2xl font-extrabold">
          {{ number_format($wallet->balance,2) }}
          <span class="text-sm text-gray-500">{{ $user->agency?->currency }}</span>
        </div>

        {{-- الدين الحالي (KPI من المبيعات المتأخرة) --}}
        <div class="{{ $currentOverdueDebt > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
          الدين الحالي: {{ number_format($currentOverdueDebt, 2) }}
        </div>
      </div>
    </div>



    @if (session()->has('message'))
      <div class="mt-3 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded p-2">
        {{ session('message') }}
      </div>
    @endif

    <hr class="my-4"/>

    <div class="grid gap-3">
      <select wire:model="type" class="border rounded px-2 py-1 text-sm">
        <option value="deposit">إيداع</option>
        <option value="withdraw">سحب</option>
        <option value="adjust">تسوية</option>
      </select>
      <input type="number" wire:model="amount" class="border rounded px-2 py-1 text-sm" placeholder="المبلغ">
      <input type="text" wire:model="reference" class="border rounded px-2 py-1 text-sm" placeholder="مرجع">
      <textarea wire:model="note" class="border rounded px-2 py-1 text-sm" placeholder="ملاحظة"></textarea>
      <x-primary-button wire:click="submit">تنفيذ</x-primary-button>
    </div>

    <hr class="my-4"/>

    <table class="w-full text-sm">
      <thead>
        <tr>
          <th class="p-2">التاريخ</th><th class="p-2">النوع</th>
          <th class="p-2">المبلغ</th><th class="p-2">الرصيد</th>
          <th class="p-2">مرجع</th><th class="p-2">منفّذ</th>
        </tr>
      </thead>
      <tbody>
        @foreach($this->transactions as $t)
          <tr>
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

    <div class="mt-2">{{ $this->transactions->links() }}</div>

    <button class="mt-6 w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl"
            wire:click="$dispatch('wallet-closed')">إغلاق</button>
  </div>
</div>
