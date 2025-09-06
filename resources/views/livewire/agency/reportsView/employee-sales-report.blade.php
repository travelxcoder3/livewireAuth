@php
    use App\Services\ThemeService;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $currency = Auth::user()?->agency?->currency ?? 'USD';

    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs';
@endphp

<div class="space-y-6">
    <!-- العنوان + أدوات التصدير -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            تقارير مبيعات الموظفين
        </h2>

        <!-- قائمة التصدير (مع تمرير الفلاتر الحالية كـ querystring) -->
        <div class="relative" x-data="{open:false}">
            <x-primary-button type="button" @click="open=!open" padding="px-4 py-2" class="flex items-center gap-2">
                <i class="fas fa-file-export"></i><span>تصدير التقرير</span>
                <i class="fas fa-chevron-down text-xs" :class="{'rotate-180':open}"></i>
            </x-primary-button>

            <div x-show="open" @click.away="open=false" x-transition
                 class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg border z-10">
                @php
                    $q = http_build_query([
                        'employeeId'        => $this->employeeId ?: null,
                        'startDate'         => $this->startDate ?: null,
                        'endDate'           => $this->endDate ?: null,
                        'serviceTypeFilter' => $this->serviceTypeFilter ?: null,
                        'providerFilter'    => $this->providerFilter ?: null,
                        'search'            => $this->search ?: null,
                        'drillType'         => $this->drillType ?: null,
                        'drillValue'        => $this->drillValue ?: null,
                    ]);
                @endphp

                <a href="{{ route('agency.reports.employee-sales.excel') . ($q ? ('?'.$q) : '') }}"
                   class="block px-4 py-2 text-sm hover:bg-gray-100">
                   <i class="fas fa-file-excel mr-2 text-green-500"></i>Excel
                </a>

                <a href="{{ route('agency.reports.employee-sales.pdf') . ($q ? ('?'.$q) : '') }}"
                   target="_blank"
                   class="block px-4 py-2 text-sm hover:bg-gray-100">
                   <i class="fas fa-file-pdf mr-2 text-red-500"></i>PDF
                </a>
            </div>
        </div>
    </div>

    <!-- الفلاتر -->
  <div class="bg-white rounded-xl shadow-md p-4">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <x-select-field 
            label="الموظف" 
            name="employee" 
            wireModel="employeeId"
            :options="$employees->pluck('name','id')->toArray()" 
            placeholder="جميع الموظفين" 
        />

        <x-select-field 
            label="نوع الخدمة" 
            name="service_type" 
            wireModel="serviceTypeFilter"
            :options="$serviceTypes->pluck('label','id')->toArray()" 
            placeholder="جميع الخدمات" 
        />

        <x-select-field 
            label="المزوّد" 
            name="provider" 
            wireModel="providerFilter"
            :options="$providers->pluck('name','id')->toArray()" 
            placeholder="جميع المزودين" 
        />

        <x-date-picker 
            name="start_date" 
            label="من تاريخ" 
            placeholder="اختر التاريخ" 
            wireModel="startDate" 
            errorName="startDate"
        />

        <x-date-picker 
            name="end_date" 
            label="إلى تاريخ" 
            placeholder="اختر التاريخ" 
            wireModel="endDate" 
            errorName="endDate"
        />
    </div>

    <div class="flex justify-end mt-4">
        <button 
            wire:click="resetFilters"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow text-sm"
        >
            إعادة تعيين الفلاتر
        </button>
    </div>
</div>


    {{-- ===========================
        الوضع 1: ملخص لكل الموظفين
        =========================== --}}
    @if (empty($this->employeeId))
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-right">اسم الموظف</th>
                        <th class="p-2 text-right">عدد العمليات</th>
                        <th class="p-2 text-right">إجمالي البيع</th>
                        <th class="p-2 text-right">إجمالي الشراء</th>
                        <th class="p-2 text-right">الربح</th>
                        <!-- 🔸 عمولة الموظف -->
                        <th class="p-2 text-right">عمولة الموظف </th>
                        <!-- المتبقي كما كان -->
                        <th class="p-2 text-right">إجمالي المستحق</th>
                        <th class="p-2 text-right">العملة</th>
                        <th class="p-2 text-right"></th> {{-- تفصيلي --}}
                    </tr>
                </thead>
                <tbody>
                    @forelse($perEmployee as $row)
                        <tr class="border-b border-[rgba(0,0,0,0.07)]">
                            <td class="p-2">{{ $row['user']?->name ?? '—' }}</td>
                            <td class="p-2">{{ $row['count'] }}</td>
                            <td class="p-2">{{ number_format($row['sell'], 2) }}</td>
                            <td class="p-2">{{ number_format($row['buy'], 2) }}</td>
                            <td class="p-2">{{ number_format($row['profit'], 2) }}</td>
                            <!-- 🔸 جديدة -->
                            <td class="p-2">{{ number_format($row['employee_commission_expected'] ?? 0, 2) }}</td>
                            <!-- المتبقي كما هو -->
                            <td class="p-2">{{ number_format($row['remaining'], 2) }}</td>
                            <td class="p-2">{{ $currency }}</td>
                            <td class="p-2">
                                <a href="#"
                                   class="text-[rgb(var(--primary-600))] hover:underline"
                                   wire:click.prevent="$set('employeeId', {{ $row['user']?->id ?? 'null' }})">
                                   تفصيلي
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="p-4 text-center text-gray-500">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    {{-- ===========================
        الوضع 2: تفصيلي لموظف محدد
        =========================== --}}
    @else
        <div class="flex items-center justify-between">
            <div class="text-lg font-semibold">
                تفاصيل الموظف:
                <span class="text-[rgb(var(--primary-700))]">
                    {{ optional($employees->firstWhere('id', $this->employeeId))->name }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <x-primary-button type="button" padding="px-3 py-2" class="text-xs"
                                  wire:click="$set('employeeId','')">
                    رجوع لقائمة الموظفين
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
                        <!-- 🔸 عمولة الموظف -->
                        <th class="p-2 text-right">عمولة الموظف </th>
                        <!-- المتبقي -->
                        <th class="p-2 text-right">المستحق</th>
                        <th class="p-2 text-right"></th> {{-- تفصيلي --}}
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
                            <!-- 🔸 جديدة -->
                            <td class="p-2">{{ number_format($row['employee_commission_expected'] ?? 0,2) }}</td>
                            <!-- المتبقي كما هو -->
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
                        <tr><td class="p-3 text-center text-gray-500" colspan="9">لا توجد بيانات</td></tr>
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
                        <!-- 🔸 عمولة الموظف -->
                        <th class="p-2 text-right">عمولة الموظف </th>
                        <!-- المتبقي -->
                        <th class="p-2 text-right">المستحق</th>
                        <th class="p-2 text-right"></th> {{-- تفصيلي --}}
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
                            <!-- 🔸 جديدة -->
                            <td class="p-2">{{ number_format($row['employee_commission_expected'] ?? 0,2) }}</td>
                            <!-- المتبقي كما هو -->
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
                        <tr><td class="p-3 text-center text-gray-500" colspan="9">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- شارة التصفية التفصيلية الحالية (إن وُجدت) --}}
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

        {{-- جدول العمليات التفصيلي للموظف --}}
       <div class="bg-white rounded-xl shadow-md overflow-hidden mt-3">
            <x-data-table :columns="App\Tables\SalesTable::columns(true,true)" :rows="$sales" />
        </div>


      
    @endif
</div>
