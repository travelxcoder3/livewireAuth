<div x-data="{ tab: 'collector' }" class="space-y-6">

        @php
            $typeOptions   = ['percent' => 'نسبة %', 'fixed' => 'مبلغ ثابت'];
            $basisOptions  = ['collected_amount' => 'من المبلغ المُحصَّل', 'net_margin' => 'من صافي الربح', 'employee_commission' => 'من عمولة الموظف'];
            $methodOptions = array_filter($collectionMethods, fn($k) => $k !== null, ARRAY_FILTER_USE_KEY);
    // إن كان $collectionMethods كولكشن: $methodOptions = array_filter($collectionMethods->toArray(), fn($k)=>$k!==null, ARRAY_FILTER_USE_KEY);
            $employeeOptions = $employees->pluck('name','id')->toArray();
        @endphp

        @if (session()->has('message'))
            <x-toast :message="session('message')" type="success" />
        @endif


    <div class="bg-white rounded-2xl shadow ring-1 ring-black/5 p-4 md:p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl md:text-2xl font-semibold">تهيئة عمولات الموظفين – {{ $agency->name }}</h2>
                <p class="text-xs md:text-sm text-gray-500 mt-1">ثبت نسبة عمولة الموظف من صافي الربح واضبط عمولة المُحصِّل. يمكنك وضع استثناءات لموظفين محددين.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">٪ عمولة الموظف</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0"
                               wire:model.debounce.300ms="employeeRate"
                               class="border rounded-lg px-3 py-2 w-24 text-right">
                        <span class="absolute inset-y-0 left-2 grid place-items-center text-gray-500 text-xs">%</span>
                    </div>
                </div>

                {{-- زر حفظ باستخدام كومبوننت الزر --}}
                <x-primary-button type="button" :gradient="true" wire:click="save" wire:loading.attr="disabled">
                    حفظ
                </x-primary-button>
            </div>
        </div>
    </div>

        <div class="border-b px-2 bg-white rounded-t-2xl">
        <nav class="flex gap-6">
            @foreach([ 
            'collector'=>'قواعد التحصيل',
            'overrides'=>'استثناءات موظفين',
            'debt'=>'سياسة دين الموظف',
            'sim'=>'محاكي'
            ] as $k=>$v)
            <button 
                x-on:click="tab='{{ $k }}'"
                class="relative py-3 px-1 text-sm font-semibold transition-colors duration-200"
                :class="tab==='{{ $k }}'
                ? 'text-[rgb(var(--primary-700))]'
                : 'text-gray-500 hover:text-[rgb(var(--primary-600))]'">
                
                {{ $v }}
                
                <!-- خط تحت الزر -->
                <span class="absolute bottom-0 left-0 w-full h-0.5 transition-all duration-300"
                    :class="tab==='{{ $k }}' 
                        ? 'bg-[rgb(var(--primary-600))] scale-x-100' 
                        : 'bg-transparent scale-x-0'">
                </span>
            </button>
            @endforeach
        </nav>
        </div>

       

        {{-- تبويب الاستثناءات --}}
        <div x-show="tab==='overrides'" x-cloak class="p-4 space-y-6">
        <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">قواعد مُحصّل خاصة لموظفين</h3>
                ...
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-right">
                            <th class="px-4 py-2">الموظف</th>
                            <th class="px-4 py-2">طريقة التحصيل</th>
                            <th class="px-4 py-2 w-40">نوع العمولة</th>
                            <th class="px-4 py-2 w-40">القيمة</th>
                            <th class="px-4 py-2 w-56">الأساس</th>
                            <th class="px-4 py-2 w-24"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($collectorOverrideRows as $i => $row)
                            <tr>
                                <td class="px-4 py-2 align-middle">
                                <x-select-field
                                    wireModel="collectorOverrideRows.{{ $i }}.employee_id"
                                    name="collectorOverrideRows[{{ $i }}][employee_id]"
                                    label=""
                                    placeholder="— اختر —"
                                    :options="$employeeOptions"
                                    containerClass="mt-0"
                                />
                                </td>

                                <td class="px-4 py-2 align-middle">
                                <x-select-field
                                    wireModel="collectorOverrideRows.{{ $i }}.method"
                                    name="collectorOverrideRows[{{ $i }}][method]"
                                    label=""
                                    placeholder="اختر الطريقة"
                                    :options="$methodOptions"
                                    containerClass="mt-0"
                                />
                                </td>

                                <td class="px-4 py-2 align-middle">
                                <x-select-field
                                    wireModel="collectorOverrideRows.{{ $i }}.type"
                                    name="collectorOverrideRows[{{ $i }}][type]"
                                    label=""
                                    placeholder="اختر نوعًا"
                                    :options="$typeOptions"
                                    containerClass="mt-0"
                                />
                                </td>

                                <td class="px-4 py-2 align-middle">
                                <x-input-field
                                    type="number" step="0.0001" min="0"
                                    wireModel="collectorOverrideRows.{{ $i }}.value"
                                    name="collectorOverrideRows[{{ $i }}][value]"
                                    label=""
                                    placeholder="قيمة"
                                    containerClass="mt-0"
                                />
                                </td>

                                <td class="px-4 py-2 align-middle">
                                <x-select-field
                                    wireModel="collectorOverrideRows.{{ $i }}.basis"
                                    name="collectorOverrideRows[{{ $i }}][basis]"
                                    label=""
                                    placeholder="اختر الأساس"
                                    :options="$basisOptions"
                                    containerClass="mt-0"
                                />
                                </td>

                                <td class="px-4 py-2 text-right align-middle">
                                <x-primary-button type="button" :gradient="false" color="#ef4444"
                                    padding="px-3 py-2" fontSize="text-xs" rounded="rounded-lg"
                                    class="h-10"
                                    wire:click="removeCollectorOverride({{ $i }})">حذف</x-primary-button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-gray-500 py-6">لا توجد قواعد خاصة.</td></tr>
                            @endforelse
                        </tbody>
                        </table>
            </div>
        </div>
        </div>



        {{-- قواعد المُحصّل العامة --}}
        <div x-show="tab==='collector'" x-cloak class="p-4">
            <div class="overflow-hidden rounded-xl ring-1 ring-black/5">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-right">
                            <th class="px-4 py-3 font-semibold">طريقة التحصيل</th>
                            <th class="px-4 py-3 font-semibold w-40">نوع العمولة</th>
                            <th class="px-4 py-3 font-semibold w-40">القيمة</th>
                            <th class="px-4 py-3 font-semibold w-56">الأساس</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($collectionMethods as $m => $label)
                            @php $isNone = is_null($m); @endphp
                            <tr class="hover:bg-gray-50/70">
                                <td class="px-4 py-3 font-medium">{{ $label }}</td>
                                <td class="px-4 py-3">
                                    @if($isNone)
                                        <span class="inline-flex items-center gap-2 px-2 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs">لا ينطبق</span>
                                    @else
                                       <x-select-field
                                            wireModel="collectorRules.{{ $m }}.type"
                                            name="collectorRules[{{ $m }}][type]"
                                            label="نوع العمولة"
                                            placeholder="اختر نوعًا"
                                            :options="$typeOptions"
                                        />

                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($isNone)
                                        <input disabled class="border rounded-lg px-2 py-2 w-full bg-gray-50 text-gray-400" placeholder="—">
                                    @else
                                        <input type="number" step="0.0001" min="0"
                                               wire:model.lazy="collectorRules.{{ $m }}.value"
                                               class="border rounded-lg px-3 py-2 w-full text-right" placeholder="قيمة">
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($isNone)
                                        <input disabled class="border rounded-lg px-2 py-2 w-full bg-gray-50 text-gray-400" placeholder="—">
                                    @else
                                     <x-select-field
                                        wireModel="collectorRules.{{ $m }}.basis"
                                        name="collectorRules[{{ $m }}][basis]"
                                        label="الأساس"
                                        placeholder="اختر الأساس"
                                        :options="$basisOptions"
                                    />

                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-xs text-gray-500">صف <b>بدون تحصيل</b> مرجعي فقط.</div>
        </div>

      



        {{-- سياسة الدين --}}
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
                    wire:click="save">
                    حفظ الإعدادات
                </x-primary-button>
                </div>

            </div>
      </div>


        {{-- المحاكي --}}
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











