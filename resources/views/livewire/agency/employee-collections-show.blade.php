<div class="space-y-6">
  <div class="bg-white rounded-xl shadow p-5">
    <div class="flex justify-between items-start">
      <div>
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700));">تحصيلات الموظف</h2>
        <div class="text-sm text-gray-600 mt-2">
          <div><b>الاسم:</b> {{ $employee->name }}</div>
          <div><b>الهاتف:</b> {{ $employee->phone ?? '-' }}</div>
          <div><b>القسم:</b> {{ optional($employee->department)->name ?? '-' }}</div>
          <div><b>المسمى الوظيفي:</b> {{ $employee->job_title ?? '-' }}</div>
        </div>
      </div>
  <a href="{{ url()->previous() ?: route('agency.employee-collections') }}"
   class="px-4 py-2 rounded border text-sm text-[rgb(var(--primary-700))]">رجوع</a>

    </div>
  </div>

  <div class="bg-white rounded-xl shadow p-4 grid md:grid-cols-4 gap-3 text-sm">
    <x-input-field wireModel="searchCustomer" label="اسم العميل" placeholder="ابحث" />
    <x-input-field wireModel="lastPayFrom" label="من تاريخ آخر سداد" type="date" />
    <x-input-field wireModel="lastPayTo"   label="إلى تاريخ آخر سداد" type="date" />
    <button wire:click="$set('searchCustomer','');$set('lastPayFrom',null);$set('lastPayTo',null)"
            class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded">مسح الفلاتر</button>
  </div>

  <div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-[1100px] w-full text-xs">
      <thead class="bg-gray-50">
        <tr>
          <th class="p-2 text-right">العميل</th>
          <th class="p-2 text-right">الدين</th>
          <th class="p-2 text-right">آخر سداد</th>
          <th class="p-2 text-right">تاريخ آخر سداد</th>
          <th class="p-2 text-right">عمر الدين (يوم)</th>
          <th class="p-2 text-right">نوع العميل</th>
          <th class="p-2 text-right">نوع المديونية</th>
          <th class="p-2 text-right">تجاوب العميل</th>
          <th class="p-2 text-right">الارتباط</th>
          <th class="p-2 text-right">إجراء</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $r)
          <tr class="border-t">
            <td class="p-2">{{ $r->customer_name }}</td>
            <td class="p-2 text-red-600 font-bold">{{ number_format($r->debt_amount,2) }}</td>
            <td class="p-2">{{ $r->last_paid ? number_format($r->last_paid,2) : '-' }}</td>
            <td class="p-2">{{ $r->last_paid_at ?? '-' }}</td>
            <td class="p-2">{{ $r->debt_age_days ?? '-' }}</td>
            <td class="p-2">{{ $r->account_type }}</td>
            <td class="p-2">{{ $r->debt_type }}</td>
            <td class="p-2">{{ $r->response }}</td>
            <td class="p-2">{{ $r->relation }}</td>
            <td class="p-2">
              <x-primary-button wire:click="openPay({{ $r->customer_id }})" class="text-xs">تسديد</x-primary-button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if ($showPayModal)
  <div class="fixed inset-0 z-50 bg-black/10 backdrop-blur-sm flex items-start justify-center pt-10">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 relative">
      <button wire:click="$set('showPayModal',false)"
              class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>

      <h3 class="text-xl font-bold text-center mb-4" style="color: rgb(var(--primary-700));">
        سداد للعميل: {{ $currentCustomerName }}
      </h3>

      <div class="grid md:grid-cols-2 gap-4">
        <!-- القسم الأول: تحديث الحالة -->
        <div class="border rounded-lg p-3">
          <h4 class="font-bold mb-2">تحديث حالة العميل</h4>
          <x-input-field label="اسم العميل" :disabled="true" wireModel="currentCustomerName" />
          <x-select-field label="نوع المديونية"  wireModel="currentDebtType"
            :options="$debtTypes->pluck('label','id')->toArray()" placeholder="اختر"/>
          <x-select-field label="تجاوب العميل" wireModel="currentResponseType"
            :options="$responseTypes->pluck('label','id')->toArray()" placeholder="اختر"/>
        </div>

        <!-- القسم الثاني: السداد -->
        <div class="border rounded-lg p-3">
          <h4 class="font-bold mb-2">تفاصيل السداد</h4>
          <x-input-field label="المبلغ المتبقي" :disabled="true"
              value="{{ number_format($remaining,2) }}" />
          <x-input-field label="المبلغ المدفوع" type="number" wireModel="paid_now" />
          <x-select-field label="طريقة التحصيل" wireModel="pay_method"
            :options="['cash'=>'نقدي','bank'=>'حوالة بنكية','pos'=>'نقطة بيع','online'=>'أونلاين']"
            placeholder="اختر الطريقة"/>
          <x-input-field label="ملاحظة" wireModel="note" />
          <div class="mt-3">
            <x-primary-button wire:click="savePay" class="w-full">موافق</x-primary-button>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  @if (session()->has('message'))
    <div class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded p-2">
      {{ session('message') }}
    </div>
  @endif
</div>
