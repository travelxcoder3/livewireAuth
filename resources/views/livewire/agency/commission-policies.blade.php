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

    <div class="bg-white/70 backdrop-blur rounded-2xl shadow ring-1 ring-black/5">
 <div class="flex flex-wrap gap-2 p-2">
    <x-primary-button
        :gradient="false" color="transparent" textColor="gray-700"
        padding="px-3 py-2" fontSize="text-sm" rounded="rounded-xl" shadow=""
        class="ring-1 transition"
        x-on:click="tab='collector'"
        x-bind:class="tab==='collector'
            ? 'bg-[rgb(var(--primary-50))] text-[rgb(var(--primary-700))] ring-[rgb(var(--primary-200))]'
            : 'bg-white text-gray-700 ring-gray-200'">
        قواعد التحصيل
    </x-primary-button>

    <x-primary-button
        :gradient="false" color="transparent" textColor="gray-700"
        padding="px-3 py-2" fontSize="text-sm" rounded="rounded-xl" shadow=""
        class="ring-1 transition"
        x-on:click="tab='overrides'"
        x-bind:class="tab==='overrides'
            ? 'bg-[rgb(var(--primary-50))] text-[rgb(var(--primary-700))] ring-[rgb(var(--primary-200))]'
            : 'bg-white text-gray-700 ring-gray-200'">
        استثناءات موظفين
    </x-primary-button>

    <x-primary-button
        :gradient="false" color="transparent" textColor="gray-700"
        padding="px-3 py-2" fontSize="text-sm" rounded="rounded-xl" shadow=""
        class="ring-1 transition"
        x-on:click="tab='debt'"
        x-bind:class="tab==='debt'
            ? 'bg-[rgb(var(--primary-50))] text-[rgb(var(--primary-700))] ring-[rgb(var(--primary-200))]'
            : 'bg-white text-gray-700 ring-gray-200'">
        سياسة دين الموظف
    </x-primary-button>

    <x-primary-button
        :gradient="false" color="transparent" textColor="gray-700"
        padding="px-3 py-2" fontSize="text-sm" rounded="rounded-xl" shadow=""
        class="ring-1 transition"
        x-on:click="tab='sim'"
        x-bind:class="tab==='sim'
            ? 'bg-[rgb(var(--primary-50))] text-[rgb(var(--primary-700))] ring-[rgb(var(--primary-200))]'
            : 'bg-white text-gray-700 ring-gray-200'">
        محاكي
    </x-primary-button>
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
                                        <select class="border rounded-lg px-2 py-2 w-full"
                                                wire:model="collectorRules.{{ $m }}.type">
                                            <option value="percent">نسبة %</option>
                                            <option value="fixed">مبلغ ثابت</option>
                                        </select>
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
                                        <select class="border rounded-lg px-2 py-2 w-full"
                                                wire:model="collectorRules.{{ $m }}.basis">
                                            <option value="collected_amount">من المبلغ المُحصَّل</option>
                                            <option value="net_margin">من صافي الربح</option>
                                            <option value="employee_commission">من عمولة الموظف</option>
                                        </select>
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
                                        <select wire:model="employeeRateOverridesRows.{{ $i }}.employee_id"
                                                class="border rounded-lg px-2 py-2 w-full">
                                            <option value="">— اختر —</option>
                                            @foreach($employees as $e)
                                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" min="0"
                                               wire:model.lazy="employeeRateOverridesRows.{{ $i }}.rate"
                                               class="border rounded-lg px-3 py-2 w-full text-right" placeholder="مثال 12">
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
                                        <select wire:model="collectorOverrideRows.{{ $i }}.employee_id"
                                                class="border rounded-lg px-2 py-2 w-full">
                                            <option value="">— اختر —</option>
                                            @foreach($employees as $e)
                                                <option value="{{ $e->id }}">{{ $e->name }}</option>
                                            @endforeach
                                        </select>
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
                                        <select wire:model="collectorOverrideRows.{{ $i }}.type"
                                                class="border rounded-lg px-2 py-2 w-full">
                                            <option value="percent">نسبة %</option>
                                            <option value="fixed">مبلغ ثابت</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.0001" min="0"
                                               wire:model.lazy="collectorOverrideRows.{{ $i }}.value"
                                               class="border rounded-lg px-3 py-2 w-full text-right" placeholder="قيمة">
                                    </td>
                                    <td class="px-4 py-2">
                                        <select wire:model="collectorOverrideRows.{{ $i }}.basis"
                                                class="border rounded-lg px-2 py-2 w-full">
                                            <option value="collected_amount">من المبلغ المُحصَّل</option>
                                            <option value="net_margin">من صافي الربح</option>
                                            <option value="employee_commission">من عمولة الموظف</option>
                                        </select>
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
                    <label class="text-sm text-gray-600">الأيام حتى تحويل العمولة لدَين على الموظف</label>
                    <input type="number" min="0" wire:model="daysToDebt"
                           class="mt-2 border rounded-lg px-3 py-2 w-full">
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                    <label class="text-sm text-gray-600">سلوك الدين</label>
                    <select wire:model="debtBehavior" class="mt-2 border rounded-lg px-3 py-2 w-full">
                        <option value="deduct_commission_until_paid">خصم العمولة حتى السداد</option>
                        <option value="hold_commission">تعليق العمولة حتى السداد</option>
                    </select>
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
                    <label class="text-sm text-gray-600">الموظف</label>
                    <select wire:model="sim.employee_id" class="mt-2 border rounded-lg px-3 py-2 w-full">
                        <option value="">— لأي موظف —</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                    <label class="text-sm text-gray-600">سعر الشراء</label>
                    <input type="number" step="0.01" wire:model="sim.cost" class="mt-2 border rounded-lg px-3 py-2 w-full">
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                    <label class="text-sm text-gray-600">سعر البيع</label>
                    <input type="number" step="0.01" wire:model="sim.sale" class="mt-2 border rounded-lg px-3 py-2 w-full">
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                    <label class="text-sm text-gray-600">طريقة التحصيل</label>
                    <select wire:model="sim.method" class="mt-2 border rounded-lg px-3 py-2 w-full">
                        @foreach($collectionMethods as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                    <label class="text-sm text-gray-600">المبلغ المُحصَّل</label>
                    <input type="number" step="0.01" wire:model="sim.collected" class="mt-2 border rounded-lg px-3 py-2 w-full">
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
