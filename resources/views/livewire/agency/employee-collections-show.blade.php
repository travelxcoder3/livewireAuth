<div id="emp-collections-details">
  <!-- تحسينات للموبايل فقط ≤640px. لا تأثير على الشاشات المتوسطة والكبيرة -->
  <style>
    @media (max-width:640px){
      #emp-collections-details .page-head{flex-direction:column; align-items:flex-start; gap:.5rem}
      #emp-collections-details .page-head a{width:100%; justify-content:center}

      #emp-collections-details .info-grid{grid-template-columns:1fr !important}

      #emp-collections-details .filters-grid{grid-template-columns:1fr !important; padding-top:2.25rem}
      #emp-collections-details .reset-wrap{position:static !important; inset:auto !important; width:100%}
      #emp-collections-details .reset-wrap > button{width:100%}

      #emp-collections-details .table-box{font-size:12px}
      #emp-collections-details .table-box table th,
      #emp-collections-details .table-box table td{padding:.35rem .5rem}

      #emp-collections-details .modal-grid{grid-template-columns:1fr !important}
    }
  </style>

  <div>
    <div class="space-y-6">
<x-toast /> 
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <!-- رأس -->

        <div class="flex items-center justify-between px-5 py-3 page-head">
          <h2 class="text-xl md:text-2xl font-bold" style="color: rgb(var(--primary-700));">تفاصيل تحصيلات الموظف</h2>
          <a href="{{ url()->previous()!=url()->current()?url()->previous():route('agency.employee-collections') }}"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg border text-sm
                    border-[rgb(var(--primary-300))] text-[rgb(var(--primary-700))]
                    hover:bg-[rgb(var(--primary-50))] hover:shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            رجوع
          </a>
        </div>

        <!-- جسم -->
        <div class="grid md:grid-cols-4 text-sm info-grid">
          <div class="p-4">
            <div class="text-gray-500 mb-1">الاسم</div>
            <div class="font-semibold text-gray-800">{{ $employee->name }}</div>
          </div>

          <div class="p-4">
            <div class="text-gray-500 mb-1">الهاتف</div>
            <div class="font-semibold text-gray-800">{{ $employee->phone ?? '-' }}</div>
          </div>

          <div class="p-4">
            <div class="text-gray-500 mb-1">القسم</div>
            <div class="font-semibold" style="color: rgb(var(--primary-700));">
              {{ optional($employee->department)->label ?? '-' }}
            </div>
          </div>

          <div class="p-4">
            <div class="text-gray-500 mb-1">المسمى الوظيفي</div>
            <div class="font-semibold" style="color: rgb(var(--primary-700));">
              {{ optional($employee->position)->label ?? '-' }}
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow p-4 grid md:grid-cols-4 gap-3 text-sm items-end relative filters-grid">

        <x-input-field wireModel="searchCustomer" label="اسم العميل" placeholder="ابحث" />

        <x-date-picker
            name="lastPayFrom"
            wireModel="lastPayFrom"
            label="من تاريخ آخر سداد"
            placeholder="اختر التاريخ" />

        <x-date-picker
            name="lastPayTo"
            wireModel="lastPayTo"
            label="إلى تاريخ آخر سداد"
            placeholder="اختر التاريخ" />

        <!-- زر مسح الفلاتر بمحاذاة يمين -->
        <div class="absolute top-4 left-4 reset-wrap">
          <button
              wire:click="resetFilters"
              class="w-34 h-9 rounded-lg shadow text-sm font-medium
                     bg-gray-200 hover:bg-gray-300 text-gray-800
                     flex items-center justify-center gap-2 transition">
              اعادة تعيين الفلاتر
          </button>
        </div>

      </div>

    </div>


    @php
      use App\Tables\EmployeeCollectionsByCustomerTable;
      $columns = EmployeeCollectionsByCustomerTable::columns();
    @endphp

    <div class="bg-white rounded-xl shadow table-box">
      <div class="overflow-x-auto">
        <div class="min-w-[1100px] md:min-w-0">
          <x-data-table :rows="$rows" :columns="$columns" />
        </div>
      </div>
    </div>


    @if ($showPayModal)
      <div class="fixed inset-0 z-50 bg-black/10 backdrop-blur-sm flex items-start justify-center pt-10">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 relative">
          <button wire:click="$set('showPayModal',false)"
                  class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>

          <h3 class="text-xl font-bold text-center mb-4" style="color: rgb(var(--primary-700));">
            سداد للعميل: {{ $currentCustomerName }}
          </h3>

          <div class="grid md:grid-cols-2 gap-4 modal-grid">
            <!-- القسم الأول: تحديث الحالة -->
            <div class="border rounded-lg p-3">
              <h4 class="font-bold mb-2">تحديث حالة العميل</h4>
              <x-input-field label="اسم العميل" :disabled="true" wireModel="currentCustomerName" />
              <x-select-field label="نوع العميل"  wireModel="currentDebtType"
                :options="$debtTypes->pluck('label','id')->toArray()" placeholder="اختر"/>
              <x-select-field label="نوع المديونية"  wireModel="currentDebtType"
                :options="$debtTypes->pluck('label','id')->toArray()" placeholder="اختر"/>
              <x-select-field label="تجاوب العميل" wireModel="currentResponseType"
                :options="$responseTypes->pluck('label','id')->toArray()" placeholder="اختر"/>
            </div>

            <!-- القسم الثاني: السداد -->
            <div class="border rounded-lg p-3">
              <h4 class="font-bold mb-2">تفاصيل السداد</h4>

              {{-- داخل القسم الثاني: السداد --}}
              <x-input-field
                  label="المبلغ المتبقي"
                  wireModel="remaining"
                  :disabled="true" />

              <x-input-field
                  label="المبلغ المدفوع"
                  type="number"
                  wireModel="paid_now"
                  step="0.01" />

              {{-- جديد: طريقة التحصيل لاحتساب عمولة المُحصّل --}}
              <x-select-field label="طريقة التحصيل" wireModel="collector_method"
                :options="[
                  1=>'مباشر عبر المحصّل',
                  2=>'غير مباشر متابعة الموظف',
                  3=>'عبر الموظف مباشرة',
                  4=>'متعثر مباشر',
                  5=>'متعثر غير مباشر',
                  6=>'شبه معدوم مباشر',
                  7=>'شبه معدوم غير مباشر',
                  8=>'معدوم مباشر',
                ]"
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

 
  </div>
</div>
