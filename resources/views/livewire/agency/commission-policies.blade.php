<div x-data="{ tab: 'collector' }" class="space-y-6">

    @if (session()->has('message'))
        <div class="rounded-xl px-4 py-2 text-sm bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200">
            {{ session('message') }}
        </div>
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
                    <x-input-field
                        label="" labelClass="hidden"
                        type="number"
                        wireModel="employeeRate"
                        containerClass="relative"
                        fieldClass="peer rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))]"
                        width="w-24 text-right"
                    />

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
<div class="bg-white/70 backdrop-blur rounded-2xl shadow ring-1 ring-black/5">
 <div class="flex gap-4 px-3 py-2">
    <button @click="tab='collector'"
        class="px-2 py-1 text-sm font-semibold transition"
        :class="tab==='collector'
            ? 'text-[rgb(var(--primary-700))] border-b-2 border-[rgb(var(--primary-600))]'
            : 'text-gray-500 hover:text-gray-700'">
        قواعد التحصيل
    </button>

    <button @click="tab='overrides'"
        class="px-2 py-1 text-sm font-semibold transition"
        :class="tab==='overrides'
            ? 'text-[rgb(var(--primary-700))] border-b-2 border-[rgb(var(--primary-600))]'
            : 'text-gray-500 hover:text-gray-700'">
        استثناءات موظفين
    </button>

    <button @click="tab='debt'"
        class="px-2 py-1 text-sm font-semibold transition"
        :class="tab==='debt'
            ? 'text-[rgb(var(--primary-700))] border-b-2 border-[rgb(var(--primary-600))]'
            : 'text-gray-500 hover:text-gray-700'">
        سياسة دين الموظف
    </button>

    <button @click="tab='sim'"
        class="px-2 py-1 text-sm font-semibold transition"
        :class="tab==='sim'
            ? 'text-[rgb(var(--primary-700))] border-b-2 border-[rgb(var(--primary-600))]'
            : 'text-gray-500 hover:text-gray-700'">
        محاكي
    </button>
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
                                            label="" labelClass="hidden"
                                            wireModel="collectorRules.{{ $m }}.type"
                                            :options="['percent'=>'نسبة %','fixed'=>'مبلغ ثابت']"
                                        />

                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($isNone)
                                        <input disabled class="border rounded-lg px-2 py-2 w-full bg-gray-50 text-gray-400" placeholder="—">
                                    @else
                                        <x-input-field
                                            label="" labelClass="hidden"
                                            type="number" wireModel="collectorRules.{{ $m }}.value"
                                            containerClass="relative"
                                            width="w-full text-right"
                                        />

                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($isNone)
                                        <input disabled class="border rounded-lg px-2 py-2 w-full bg-gray-50 text-gray-400" placeholder="—">
                                    @else
                                        <x-select-field
                                            label="" labelClass="hidden"
                                            wireModel="collectorRules.{{ $m }}.basis"
                                            :options="[
                                            'collected_amount'=>'من المبلغ المُحصَّل',
                                            'net_margin'=>'من صافي الربح',
                                            'employee_commission'=>'من عمولة الموظف'
                                            ]"
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

        {{-- تبويب الاستثناءات --}}
        <div x-show="tab==='overrides'" x-cloak class="p-4 space-y-6">
            {{-- نسبة موظف خاصة --}}
            <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">نِسب خاصة لموظفين</h3>
                    <x-primary-button type="button" :gradient="false" color="rgb(var(--primary-600))"
                              padding="px-3 py-1" fontSize="text-sm"
                              wire:click="addEmpRateOverride">
                        إضافة
                    </x-primary-button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-right">
                                <th class="px-4 py-2">الموظف</th>
                                <th class="px-4 py-2 w-40">٪ من صافي الربح</th>
                                <th class="px-4 py-2 w-24"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($employeeRateOverridesRows as $i => $row)
                                <tr>
                                    <td class="px-4 py-2">
                                        <x-select-field
                                            label="" labelClass="hidden"
                                            wireModel="employeeRateOverridesRows.{{ $i }}.employee_id"
                                            :options="$employees->pluck('name','id')->toArray()"
                                            placeholder="— اختر —"
                                        />

                                    </td>
                                    <td class="px-4 py-2">
                                        <x-input-field
                                            label="" labelClass="hidden"
                                            type="number" wireModel="employeeRateOverridesRows.{{ $i }}.rate"
                                            width="w-full text-right"
                                        />

                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <x-primary-button type="button" :gradient="false" color="#ef4444"
                                                  padding="px-3 py-1" fontSize="text-sm"
                                                  wire:click="removeEmpRateOverride({{ $i }})">
                                            حذف
                                        </x-primary-button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-gray-500 py-6">لا توجد نسب خاصة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- قواعد مُحصّل خاصة --}}
            <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">قواعد مُحصّل خاصة لموظفين</h3>
                    <x-primary-button type="button" :gradient="false" color="rgb(var(--primary-600))"
                              padding="px-3 py-1" fontSize="text-sm"
                              wire:click="addCollectorOverride">
                        إضافة
                    </x-primary-button>
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
                                    <td class="px-4 py-2">
                                    <x-select-field label="" labelClass="hidden"
                                        wireModel="collectorOverrideRows.{{ $i }}.employee_id"
                                        :options="$employees->pluck('name','id')->toArray()" placeholder="— اختر —"/>

                                    </td>
                                    <td class="px-4 py-2">
                                        <select wire:model="collectorOverrideRows.{{ $i }}.method"
                                                class="border rounded-lg px-2 py-2 w-full">
                                            @foreach($collectionMethods as $k=>$v)
                                                @if(!is_null($k))
                                                    <option value="{{ $k }}">{{ $v }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <x-select-field label="" labelClass="hidden"
                                            wireModel="collectorOverrideRows.{{ $i }}.type"
                                            :options="['percent'=>'نسبة %','fixed'=>'مبلغ ثابت']"/>

                                    </td>
                                    <td class="px-4 py-2">
                                        <x-input-field label="" labelClass="hidden"
                                            type="number" wireModel="collectorOverrideRows.{{ $i }}.value"
                                            width="w-full text-right"/>

                                    </td>
                                    <td class="px-4 py-2">
                                        <x-select-field label="" labelClass="hidden"
                                            wireModel="collectorOverrideRows.{{ $i }}.basis"
                                            :options="[
                                            'collected_amount'=>'من المبلغ المُحصَّل',
                                            'net_margin'=>'من صافي الربح',
                                            'employee_commission'=>'من عمولة الموظف'
                                            ]"/>

                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <x-primary-button type="button" :gradient="false" color="#ef4444"
                                                  padding="px-3 py-1" fontSize="text-sm"
                                                  wire:click="removeCollectorOverride({{ $i }})">
                                            حذف
                                        </x-primary-button>
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

        {{-- سياسة الدين --}}
        <div x-show="tab==='debt'" x-cloak class="p-4">
            <div class="grid md:grid-cols-3 gap-4">
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                        <x-input-field label="الأيام حتى تحويل المتبقي من التحصيل لدَين على الموظف"
                            type="number" wireModel="daysToDebt"/>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                        <x-select-field label="سلوك الدين" wireModel="debtBehavior"
                            :options="[
                            'deduct_commission_until_paid'=>'خصم العمولة حتى السداد',
                            'hold_commission'=>'تعليق العمولة حتى السداد'
                            ]"/>
                </div>
                <div class="flex items-end">
                    <x-primary-button type="button" :gradient="false" color="rgb(var(--primary-600))"
                              padding="px-5 py-3" class="w-full md:w-auto"
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
                        <x-select-field label="الموظف" wireModel="sim.employee_id"
                            :options="$employees->pluck('name','id')->toArray()" placeholder="— لأي موظف —"/>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                        <x-input-field label="سعر البيع" type="number" wireModel="sim.sale"/>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                    <x-input-field label="التكلفة"   type="number" wireModel="sim.cost"/>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                    <x-select-field label="طريقة التحصيل" wireModel="sim.method"
                        :options="collect($collectionMethods)->toArray()"/>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                    <x-input-field label="المبلغ المُحصَّل" type="number" wireModel="sim.collected"/>
                </div>
                <div class="flex items-end">
                    <x-primary-button type="button" :gradient="false" color="rgb(var(--primary-600))"
                              padding="px-5 py-3" class="w-full"
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
