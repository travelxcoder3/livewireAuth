<div class="space-y-6">
  <div class="bg-white rounded-xl shadow p-5">
    <h2 class="text-2xl font-bold mb-4 text-[rgb(var(--primary-700))]">العملاء</h2>

    <x-input-field name="search" label="ابحث باسم العميل" wireModel="search" placeholder="مثال: أحمد سعيد" width="w-full" />

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm text-right divide-y">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-3 py-2">#</th>
            <th class="px-3 py-2">الاسم</th>
            <th class="px-3 py-2">الهاتف</th>
            <th class="px-3 py-2">الإجراء</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($customers as $i => $c)
            <tr class="hover:bg-gray-50">
              <td class="px-3 py-2">{{ $customers->firstItem() + $i }}</td>
              <td class="px-3 py-2">{{ $c->name }}</td>
              <td class="px-3 py-2">{{ $c->phone ?? '—' }}</td>
              <td class="px-3 py-2">
                <a href="{{ route('agency.statements.customer', $c->id) }}"
                   class="text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold">تفاصيل</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center text-gray-400 py-6">لا يوجد عملاء.</td></tr>
          @endforelse
        </tbody>
      </table>
      <div class="mt-3">{{ $customers->links() }}</div>
    </div>
  </div>
</div>
