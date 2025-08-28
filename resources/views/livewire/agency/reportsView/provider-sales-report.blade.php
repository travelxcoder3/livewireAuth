
@php
    use App\Services\ThemeService;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $currency = Auth::user()?->agency?->currency ?? 'USD';

    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs';
@endphp

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            تقارير مبيعات المزوّدين
        </h2>

        <div class="relative" x-data="{open:false}">
            <x-primary-button type="button" @click="open=!open" padding="px-4 py-2" class="flex items-center gap-2">
                <i class="fas fa-file-export"></i><span>تصدير التقرير</span>
                <i class="fas fa-chevron-down text-xs" :class="{'rotate-180':open}"></i>
            </x-primary-button>

            <div x-show="open" @click.away="open=false" x-transition
                 class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg border z-10">
                @php
                    $q = http_build_query([
                        'providerId'        => $this->providerId ?: null,
                        'startDate'         => $this->startDate ?: null,
                        'endDate'           => $this->endDate ?: null,
                        'serviceTypeFilter' => $this->serviceTypeFilter ?: null,
                        'employeeFilter'    => $this->employeeFilter ?: null,
                        'search'            => $this->search ?: null,
                        'drillType'         => $this->drillType ?: null,
                        'drillValue'        => $this->drillValue ?: null,
                    ]);
                @endphp

                <a href="{{ route('agency.reports.provider-sales.excel') . ($q ? ('?'.$q) : '') }}"
                   class="block px-4 py-2 text-sm hover:bg-gray-100">
                   <i class="fas fa-file-excel mr-2 text-green-500"></i>Excel
                </a>

                <a href="{{ route('agency.reports.provider-sales.pdf') . ($q ? ('?'.$q) : '') }}"
                   target="_blank"
                   class="block px-4 py-2 text-sm hover:bg-gray-100">
                   <i class="fas fa-file-pdf mr-2 text-red-500"></i>PDF
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-3 gap-4">
            <x-select-field label="المزوّد" name="provider" wireModel="providerId"
                :options="$providers->pluck('name','id')->toArray()" placeholder="جميع المزودين" />

            <x-select-field label="نوع الخدمة" name="service_type" wireModel="serviceTypeFilter"
                :options="$serviceTypes->pluck('label','id')->toArray()" placeholder="جميع الخدمات" />

            <x-select-field label="الموظف" name="employee" wireModel="employeeFilter"
                :options="$employees->pluck('name','id')->toArray()" placeholder="جميع الموظفين" />

            <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold text-gray-600">من تاريخ</label>
                <input type="date" wire:model="startDate" class="{{ $fieldClass }}">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold text-gray-600">إلى تاريخ</label>
                <input type="date" wire:model="endDate" class="{{ $fieldClass }}">
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button wire:click="resetFilters"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm">
                إعادة تعيين الفلاتر
            </button>
        </div>
    </div>

    {{-- الوضع 1: ملخص لكل المزوّدين --}}
    @if (empty($this->providerId))
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-right">اسم المزوّد</th>
                        <th class="p-2 text-right">عدد العمليات</th>
                        <th class="p-2 text-right">إجمالي الشراء</th>
                        <th class="p-2 text-right">إجمالي البيع</th>
                        <th class="p-2 text-right">الربح</th>
                        <th class="p-2 text-right">إجمالي المستحق</th>
                        <th class="p-2 text-right">العملة</th>
                        <th class="p-2 text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($perProvider as $row)
                        <tr class="border-b border-[rgba(0,0,0,0.07)]">
                            <td class="p-2">{{ $row['provider']?->name ?? '—' }}</td>
                            <td class="p-2">{{ $row['count'] }}</td>
                            <td class="p-2">{{ number_format($row['buy'], 2) }}</td>
                            <td class="p-2">{{ number_format($row['sell'], 2) }}</td>
                            <td class="p-2">{{ number_format($row['profit'], 2) }}</td>
                            <td class="p-2">{{ number_format($row['remaining'], 2) }}</td>
                            <td class="p-2">{{ $currency }}</td>
                            <td class="p-2">
                                <a href="#"
                                   class="text-[rgb(var(--primary-600))] hover:underline"
                                   wire:click.prevent="$set('providerId', {{ $row['provider']?->id ?? 'null' }})">
                                   تفصيلي
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-4 text-center text-gray-500">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    {{-- الوضع 2: تفصيلي لمزوّد محدد --}}
    @else
        <div class="flex items-center justify-between">
            <div class="text-lg font-semibold">
                تفاصيل المزوّد:
                <span class="text-[rgb(var(--primary-700))]">
                    {{ optional($providers->firstWhere('id', $this->providerId))->name }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <x-primary-button type="button" padding="px-3 py-2" class="text-xs"
                                  wire:click="$set('providerId','')">
                    رجوع لقائمة المزوّدين
                </x-primary-button>
            </div>
        </div>

        {{-- تفصيلي حسب الخدمة --}}
        <div class="bg-white rounded-xl shadow-md overflow-hidden mt-3">
            <div class="px-4 py-3 font-semibold text-gray-700">تفصيلي حسب الخدمة</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-right">الخدمة</th>
                        <th class="p-2 text-right">عدد</th>
                        <th class="p-2 text-right">البيع</th>
                        <th class="p-2 text-right">الشراء</th>
                        <th class="p-2 text-right">الربح</th>
                        <th class="p-2 text-right">المستحق</th>
                        <th class="p-2 text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byService as $serviceId => $row)
                        <tr class="border-b border-[rgba(0,0,0,0.07)]">
                            <td class="p-2">{{ $row['firstRow']?->service?->label ?? '—' }}</td>
                            <td class="p-2">{{ $row['count'] }}</td>
                            <td class="p-2">{{ number_format($row['sell'],2) }}</td>
                            <td class="p-2">{{ number_format($row['buy'],2) }}</td>
                            <td class="p-2">{{ number_format($row['profit'],2) }}</td>
                            <td class="p-2">{{ number_format($row['remaining'],2) }}</td>
                            <td class="p-2">
                                <a href="#"
                                   class="text-[rgb(var(--primary-600))] hover:underline"
                                   wire:click.prevent="setDrill('service','{{ $serviceId }}')">
                                   تفصيلي
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-3 text-center text-gray-500" colspan="7">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- تفصيلي حسب الشهر --}}
        <div class="bg-white rounded-xl shadow-md overflow-hidden mt-3">
            <div class="px-4 py-3 font-semibold text-gray-700">تفصيلي حسب الشهر</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-right">الشهر</th>
                        <th class="p-2 text-right">عدد</th>
                        <th class="p-2 text-right">البيع</th>
                        <th class="p-2 text-right">الشراء</th>
                        <th class="p-2 text-right">الربح</th>
                        <th class="p-2 text-right">المستحق</th>
                        <th class="p-2 text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byMonth as $ym => $row)
                        <tr class="border-b border-[rgba(0,0,0,0.07)]">
                            <td class="p-2">{{ $ym }}</td>
                            <td class="p-2">{{ $row['count'] }}</td>
                            <td class="p-2">{{ number_format($row['sell'],2) }}</td>
                            <td class="p-2">{{ number_format($row['buy'],2) }}</td>
                            <td class="p-2">{{ number_format($row['profit'],2) }}</td>
                            <td class="p-2">{{ number_format($row['remaining'],2) }}</td>
                            <td class="p-2">
                                <a href="#"
                                   class="text-[rgb(var(--primary-600))] hover:underline"
                                   wire:click.prevent="setDrill('month','{{ $ym }}')">
                                   تفصيلي
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-3 text-center text-gray-500" colspan="7">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- شارة التصفية التفصيلية الحالية --}}
        @if($this->drillType && $this->drillValue)
            <div class="flex items-center gap-3 mt-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-blue-50 border border-blue-200">
                    عرض العمليات تفصيليًا حسب
                    @if($this->drillType === 'service')
                        <strong class="mx-1">الخدمة</strong>:
                        {{ optional($serviceTypes->firstWhere('id', (int)$this->drillValue))->label ?? $this->drillValue }}
                    @else
                        <strong class="mx-1">الشهر</strong>: {{ $this->drillValue }}
                    @endif
                </span>

                <button wire:click="clearDrill"
                        class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200 border">
                    إلغاء التفصيلي
                </button>
            </div>
        @endif

        {{-- جدول العمليات التفصيلي للمزوّد --}}
        <div class="bg-white rounded-xl shadow-md overflow-hidden mt-3">
            <x-data-table :columns="App\Tables\SalesTable::columns(true,true)" :rows="$sales" />
        </div>
    @endif
</div>