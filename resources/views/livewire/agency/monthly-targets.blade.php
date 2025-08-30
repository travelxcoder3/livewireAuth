<div x-data="{ tab: @entangle('tab') }">

    <x-toast :message="$successMessage" :type="$toastType ?? 'success'" />

    <div class="bg-white rounded-2xl shadow ring-1 ring-black/5 p-3">
        <nav class="flex gap-6 border-b">
            <button x-on:click="tab='emp'" class="relative py-3 px-1 text-sm font-semibold" :class="tab==='emp' ? 'text-[rgb(var(--primary-700))]' : 'text-gray-500'">
                تهيئة عمولات الموظفين
            </button>
            <button x-on:click="tab='collector'" class="relative py-3 px-1 text-sm font-semibold" :class="tab==='collector' ? 'text-[rgb(var(--primary-700))]' : 'text-gray-500'">
                قواعد التحصيل الشهرية
            </button>
            <button x-on:click="tab='debt'" class="relative py-3 px-1 text-sm font-semibold" :class="tab==='debt' ? 'text-[rgb(var(--primary-700))]' : 'text-gray-500'">
                سياسة دين الموظف
            </button>
            <button x-on:click="tab='sim'" class="relative py-3 px-1 text-sm font-semibold" :class="tab==='sim' ? 'text-[rgb(var(--primary-700))]' : 'text-gray-500'">
                المحاكي
            </button>
        </nav>
        {{-- تبويب 1: عمولات الموظفين --}}
        <div x-show="tab==='emp'" x-cloak class="space-y-4 pt-4">
            <div class="flex items-center gap-3">
                <label class="text-sm text-gray-700">٪ عمولة الموظف (تُثبت مرة واحدة)</label>
                <div class="relative">
                    <input type="number" step="0.01" min="0" class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-right"
                        wire:model.lazy="employeeRateFixed" @disabled($employeeRateLocked)>
                    <span class="absolute inset-y-0 left-2 grid place-items-center text-gray-500 text-xs">%</span>
                </div>

                @if(!$employeeRateLocked)
                    <x-primary-button type="button" :gradient="true" wire:click="fixEmployeeRate">تثبيت نهائي</x-primary-button>
                @else
                    <span class="text-xs px-2 py-1 rounded-lg bg-gray-100 text-gray-600">مقفول</span>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <div class="flex items-center gap-2">
                    <input type="number" min="2000" wire:model.lazy="empYear"  class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                    <input type="number" min="1" max="12" wire:model.lazy="empMonth" class="w-16 rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                        class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-xl text-sm"
                        wire:click="copyEmpFromPrev">
                        نسخ من الشهر السابق
                    </button>

                    <!-- زر "حفظ الكل لمرة واحدة" تم نقله هنا بجانب زر "نسخ من الشهر السابق" -->
                    <x-primary-button type="button" :gradient="true" wire:click="saveAll">
                        حفظ الكل لمرة واحدة
                    </x-primary-button>
                </div>
            </div>
        </div>
            <div x-show="tab==='emp'" x-cloak class="space-y-4 pt-4">
                            <div class="bg-white rounded-xl shadow-md overflow-hidden">

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                        <thead class="bg-gray-100 text-gray-600">
                        <tr>
                            <th class="px-2 py-1">الموظف</th>
                            <th class="px-2 py-1">الهدف الأساسي</th>
                            <th class="px-2 py-1">هدف المقارنة</th>
                            <th class="px-2 py-1">العمولة الخاصة للموظف</th>
                            <th class="px-2 py-1 text-center">قفل</th>
                            <th class="px-2 py-1 w-24 text-center">حفظ</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($rows as $i => $r)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1 font-medium text-gray-800">{{ $r['name'] }}</td>
                                <td class="px-2 py-1">
                                    <input type="number" step="0.01" wire:model.defer="rows.{{ $i }}.main_target"
                                        class="w-36 rounded-lg border border-gray-300 px-2 py-1 text-xs" @disabled($r['locked'])>
                                </td>
                                <td class="px-2 py-1">
                                    <input type="number" step="0.01" wire:model.defer="rows.{{ $i }}.sales_target"
                                        class="w-36 rounded-lg border border-gray-300 px-2 py-1 text-xs" @disabled($r['locked'])>
                                </td>
                                <td class="px-2 py-1">
                                    <input type="number" step="0.01" wire:model.defer="rows.{{ $i }}.override_rate"
                                        class="w-28 rounded-lg border border-gray-300 px-2 py-1 text-xs" @disabled($r['locked'])>
                                </td>
                                <td class="px-2 py-1 text-center">
                                    <span class="px-3 py-1 rounded-xl text-xs font-bold {{ $r['locked'] ? 'bg-gray-100 text-gray-600' : 'bg-[rgb(var(--primary-600))] text-white' }}">
                                        {{ $r['locked'] ? 'مقفول' : 'مفتوح' }}
                                    </span>
                                </td>
                                    <td class="px-2 py-1 text-center">
                                        <x-primary-button type="button"
                                            :gradient="true"
                                            wire:click="saveRow({{ $i }})"
                                            :disabled="$r['locked']"
                                            class="px-3 py-1 text-xs h-auto">
                                            حفظ
                                        </x-primary-button>
                                    </td>


                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-gray-400">لا توجد بيانات</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- تبويب 2: قواعد التحصيل الشهرية --}}
        <div x-show="tab==='collector'" x-cloak class="space-y-6 pt-4">

            {{-- (أ) القواعد الأساسية – تُثبت مرة واحدة --}}
            <div class="rounded-xl bg-white p-4 ring-1 ring-black/5 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold">قواعد التحصيل الأساسية (تُثبت مرة واحدة)</h3>
                        <p class="text-xs text-gray-500">هذه هي القيم المرجعية لجميع الأشهر. بعد التثبيت تُقفل نهائيًا.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(!$collectorBaselinesLocked)
                            <x-primary-button type="button" :gradient="true" wire:click="fixCollectorBaselines">
                                تثبيت نهائي
                            </x-primary-button>
                        @else
                            <span class="text-xs px-2 py-1 rounded-lg bg-gray-100 text-gray-600">مقفول</span>
                        @endif
                    </div>
                </div>

                @php
                    $labels = [
                        1=>'مباشر عبر المحصّل',2=>'غير مباشر متابعة الموظف',3=>'عبر الموظف مباشرة',
                        4=>'متعثر مباشر',5=>'متعثر غير مباشر',6=>'شبه معدوم مباشر',7=>'شبه معدوم غير مباشر',8=>'معدوم مباشر'
                    ];
                    $typeOptions=['percent'=>'نسبة %','fixed'=>'مبلغ ثابت'];
                    $basisOptions=['collected_amount'=>'من المبلغ المُحصَّل','net_margin'=>'من صافي الربح','employee_commission'=>'من عمولة الموظف'];
                @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
  @foreach([1,2,3,4,5,6,7,8] as $m)
    <div class="rounded-lg border border-gray-300 bg-white p-2 shadow-sm">
      <div class="flex items-center justify-between mb-1">
        <div class="font-semibold text-[13px]">{{ $labels[$m] }}</div>
            <span class="text-[10px] px-2 py-0.5 rounded {{ $collectorBaselinesLocked ? 'bg-gray-100 text-gray-600' : 'bg-[rgb(var(--primary-500))] text-white' }}">
            {{ $collectorBaselinesLocked ? 'مقفول' : 'قابل للتثبيت' }}
            </span>
      </div>

      <div class="grid grid-cols-2 gap-2 text-xs">
<x-select-field
    label=""
    :options="$typeOptions"
    wire-model="collectorBaselines.{{ $m }}.type"
    placeholder="نوع العمولة"
    container-class="relative m-0"
    :disabled="$collectorBaselinesLocked"
    compact
/>



        <input type="number" step="0.0001" min="0"
               class="rounded-lg border border-gray-300 px-2 py-1.5 text-right"
               wire:model.lazy="collectorBaselines.{{ $m }}.value"
               @disabled($collectorBaselinesLocked)>
      </div>
    </div>
  @endforeach
</div>

            </div>

{{-- شريط التحكم لهذا الشهر (داخل تبويب التحصيل وفوق الجدول) --}}
<div class="flex flex-wrap items-center justify-between gap-2 mb-2">
  <div class="flex items-center gap-2">
    <input type="number" min="2000" wire:model.lazy="colYear"  class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm" />
    <input type="number" min="1" max="12" wire:model.lazy="colMonth" class="w-16 rounded-lg border border-gray-300 px-3 py-2 text-sm" />
  </div>
  <div class="flex items-center gap-2">
    <button type="button"
            class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-xl text-sm"
            wire:click="copyCollectorFromPrev">
    نسخ من الشهر السابق
    </button>

    <x-primary-button type="button" :gradient="true" wire:click="createCollectorForMonth">
      إنشاء ضبط هذا الشهر وقفله
    </x-primary-button>
  </div>
</div>



                <div class="overflow-x-auto rounded-xl ring-1 ring-black/5">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-right">
                                <th class="px-3 py-2">طريقة التحصيل</th>
                                <th class="px-3 py-2 w-40">نوع العمولة</th>
                                <th class="px-3 py-2 w-40">القيمة</th>
                                <th class="px-3 py-2 w-24">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach([1,2,3,4,5,6,7,8] as $m)
                                @php $row = $collectorMonthly[$m]; @endphp
                                <tr>
                                    <td class="px-3 py-2 font-medium">{{ $labels[$m] }}</td>
                                    <td class="px-3 py-2">
<x-select-field
    label=""
    :options="$typeOptions"
    wire-model="collectorMonthly.{{ $m }}.type"
    placeholder="نوع العمولة"
    container-class="relative m-0"
    :disabled="$row['exists']"
    compact
/>


                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.0001" min="0" class="border border-gray-300 rounded-lg px-3 py-2 w-full text-right"
                                               wire:model.lazy="collectorMonthly.{{ $m }}.value"
                                               @disabled($row['exists'])>
                                    </td>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="text-xs px-2 py-1 rounded-lg {{ $row['exists'] ? 'bg-gray-100 text-gray-600' : 'bg-[rgb(var(--primary-600))] text-white' }}">
                                            {{ $row['exists'] ? 'مقفول' : 'جديد' }}
                                        </span>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
<!-- تبويب سياسة دين الموظف -->
<div x-show="tab==='debt'" x-cloak class="p-4">
    <div class="grid md:grid-cols-3 gap-4">
        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
            <x-input-field
                type="number" min="0"
                wireModel="daysToDebt"
                name="daysToDebt"
                label="الأيام حتى تحويل العمولة لدَين على الموظف"
                placeholder="0"
            />
        </div>

        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
            <x-select-field
                wireModel="debtBehavior"
                name="debtBehavior"
                label="سلوك الدين"
                placeholder="اختر السلوك"
                :options="[ 
                    'deduct_commission_until_paid' => 'خصم العمولة حتى السداد', 
                    'hold_commission' => 'تعليق العمولة حتى السداد'
                ]"
            />
        </div>

        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5 flex items-end">
            <x-primary-button
                type="button"
                :gradient="false"
                color="rgb(var(--primary-600))"
                padding="px-3"
                fontSize="text-xs"
                rounded="rounded-lg"
                width="w-full"
                class="h-10"
                wire:click="saveDebtPolicy">
                حفظ الإعدادات
            </x-primary-button>
        </div>
    </div>
</div>
<!-- تبويب المحاكي -->
<div x-show="tab==='sim'" x-cloak class="p-4 space-y-4">
    <div class="grid md:grid-cols-6 gap-3">
        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
            <x-select-field
                wireModel="sim.employee_id"
                name="sim_employee_id"
                label="الموظف"
                placeholder="— لأي موظف —"
                :options="$employeeOptions"
            />
        </div>

        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
            <x-input-field
                type="number" step="0.01"
                wireModel="sim.cost"
                name="sim_cost"
                label="سعر الشراء"
                placeholder="0.00"
            />
        </div>

        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
            <x-input-field
                type="number" step="0.01"
                wireModel="sim.sale"
                name="sim_sale"
                label="سعر البيع"
                placeholder="0.00"
            />
        </div>

        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
            <x-select-field
                wireModel="sim.method"
                name="sim_method"
                label="طريقة التحصيل"
                placeholder="اختر الطريقة"
                :options="$methodOptions"
            />
        </div>

        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
            <x-input-field
                type="number" step="0.01"
                wireModel="sim.collected"
                name="sim_collected"
                label="المبلغ المُحصَّل"
                placeholder="0.00"
            />
        </div>

        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5 flex items-end">
            <x-primary-button
                type="button"
                :gradient="false"
                color="rgb(var(--primary-600))"
                padding="px-3"           
                fontSize="text-xs"     
                rounded="rounded-lg"     
                width="w-full"            
                class="h-10"              
                wire:click="simulate">
                احسب
            </x-primary-button>
        </div>
    </div>

    @if($sim['result'])
        <div class="grid md:grid-cols-4 gap-4">
            <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                <div class="text-xs text-gray-500">صافي الربح</div>
                <div class="text-xl font-semibold mt-1">{{ $sim['result']['net_margin'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                <div class="text-xs text-gray-500">عمولة الموظف</div>
                <div class="text-xl font-semibold mt-1">{{ $sim['result']['employee_commission'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                <div class="text-xs text-gray-500">عمولة المُحصّل</div>
                <div class="text-xl font-semibold mt-1">{{ $sim['result']['collector_commission'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                <div class="text-xs text-gray-500">نصيب الشركة</div>
                <div class="text-xl font-semibold mt-1">{{ $sim['result']['company_share'] }}</div>
            </div>
        </div>            
    @endif
</div>

        </div>
    </div>
</div>
