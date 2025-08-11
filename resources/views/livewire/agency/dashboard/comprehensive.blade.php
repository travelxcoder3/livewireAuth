@php $stats = $this->comprehensiveStats; @endphp

<div class="space-y-6">
    <!-- Welcome Header with License Info -->
    <div class="rounded-xl p-6 mb-6" style="background: linear-gradient(135deg, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- العنوان الترحيبي في اليسار -->
            <div class="md:order-1 order-2 md:text-left text-center w-full md:w-auto">
                <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">مرحباً بك في {{ $this->agencyInfo->name }}</h1>
                <p class="text-white/90 text-sm">
                    @switch($this->dashboardType)
                        @case('comprehensive') لوحة التحكم الشاملة - عرض جميع الإحصائيات @break
                        @case('roles-focused') لوحة التحكم - إدارة الأدوار @break
                        @case('users-focused') لوحة التحكم - إدارة المستخدمين @break
                        @case('permissions-focused') لوحة التحكم - إدارة الصلاحيات @break
                        @case('service-types-focused') لوحة تحكم إدارة الخدمات @break
                        @case('sales-focused') لوحة تحكم إدارة المبيعات @break
                        @case('hr-focused') لوحة تحكم إدارة الموارد البشرية @break
                        @default لوحة التحكم المبسطة
                    @endswitch
                </p>
            </div>

            <!-- شبكة الأيقونات السريعة في المنتصف -->
            @php
                $showCompanyInfo = Auth::user()->hasRole('agency-admin');
            @endphp
            @if($showCompanyInfo)
            <div class="md:order-2 order-1 flex justify-center w-full md:w-auto my-4 md:my-0">
                <div class="grid grid-cols-4 sm:grid-cols-8 gap-2 sm:gap-4 w-full max-w-xs sm:max-w-none mx-auto">
                    @php
                        $quickLinks = [
                            ['route' => 'agency.users', 'icon' => 'users', 'title' => 'المستخدمين', 'desc' => 'إدارة مستخدمي الوكالة'],
                            ['route' => 'agency.roles', 'icon' => 'user-tag', 'title' => 'الأدوار', 'desc' => 'إدارة أدوار المستخدمين'],
                            ['route' => 'agency.accounts', 'icon' => 'money-bill', 'title' => 'الحسابات', 'desc' => 'إدارة الحسابات '],
                            ['route' => 'agency.providers', 'icon' => 'briefcase', 'title' => 'المزودين', 'desc' => 'إدارة مزودي الخدمات'],
                            ['route' => 'agency.customers.add', 'icon' => 'user-plus', 'title' => 'إضافة عميل', 'desc' => 'إضافة عميل جديد'],
                            ['route' => 'agency.hr.employees.index', 'icon' => 'user-tie', 'title' => 'الموظفين', 'desc' => 'إدارة موظفي الوكالة'],
                            ['route' => 'agency.sales.index', 'icon' => 'chart-line', 'title' => 'المبيعات', 'desc' => 'تقارير وإحصائيات المبيعات'],
                            ['route' => 'agency.profile', 'icon' => 'user-circle', 'title' => 'الملف الشخصي', 'desc' => 'إدارة ملفك الشخصي'],
                        ];
                    @endphp
                    @foreach($quickLinks as $link)
                        <a href="{{ route($link['route']) }}"
                           class="relative flex items-center justify-center w-12 h-12 md:w-16 md:h-16 rounded-xl shadow transition group border border-gray-100 bg-[rgb(var(--primary-500))] hover:bg-white"
                           style="font-size: 1.5rem;">
                            <i class="fas fa-{{ $link['icon'] }} text-white group-hover:text-[rgb(var(--primary-500))] transition-colors"></i>
                            <span class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-full px-3 py-1 rounded-lg bg-black/80 text-white text-xs opacity-0 group-hover:opacity-100 pointer-events-none transition whitespace-nowrap z-50">
                                {{ $link['title'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- معلومات الترخيص في اليمين -->
            <div class="md:order-3 order-3 mt-4 md:mt-0 bg-white/10 backdrop-blur-sm rounded-lg p-4 min-w-[220px]">
                <div class="flex flex-col items-end gap-2">
                    <div class="flex items-center gap-2 mt-1 justify-end">
                        <div class="bg-white/80 rounded-lg px-3 py-1 shadow text-primary-800 flex items-center gap-2" style="font-size: 13px;">
                            <i class="fas fa-calendar-alt text-primary-500"></i>
                            <span>انتهاء الاشتراك بالنظام : </span>
                        </div>
                    </div>
                    <div class="text-right">
                        @php
                            $expiry = $this->agencyInfo->subscription_end_date;
                            $now = now();
                            $diff = $now->diff($expiry);
                            $years = $diff->y;
                            $days = $diff->days - ($years * 365);
                        @endphp
                        @if($expiry->isPast())
                            <span class="px-2 py-1 text-xs font-bold rounded-full bg-red-500/20 text-red-100">منتهي</span>
                        @elseif($years > 0 || $days > 0)
                            <span class="px-2 py-1 text-xs font-bold rounded-full bg-amber-500/20 text-amber-100">
                                ينتهي خلال
                                @if($years > 0)
                                    {{ $years }} سنة{{ $years > 1 ? 's' : '' }}
                                @endif
                                @if($years > 0 && $days > 0)
                                    و
                                @endif
                                @if($days > 0)
                                    {{ $days }} يوم
                                @endif
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-bold rounded-full bg-green-500/20 text-green-100">نشط</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Personal Sales Target Card for All Users -->
    @php
        $userTarget = Auth::user()->sales_target ?? 0;
        $userMonthlySales = \App\Models\Sale::where('user_id', Auth::id())
            ->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->sum('usd_sell');
        $targetPercentage = $userTarget > 0 ? min(($userMonthlySales / $userTarget) * 100, 100) : 0;
    @endphp
    
    @if($userTarget > 0)
    <div class="bg-white rounded-xl shadow-md p-6 mb-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-bullseye text-orange-500"></i>
                الهدف الشخصي للشهر الحالي
            </h3>
            <div class="text-sm text-gray-500">
                {{ now()->format('F Y') }}
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- الهدف المحدد -->
            <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg p-4 text-center">
                <div class="text-orange-600 text-2xl font-bold">
                    ${{ number_format($userTarget, 0) }}
                </div>
                <div class="text-orange-700 text-sm font-medium mt-1">
                    الهدف المحدد
                </div>
            </div>
            
            <!-- المبلغ المحقق -->
            <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4 text-center">
                <div class="text-green-600 text-2xl font-bold">
                    ${{ number_format($userMonthlySales, 0) }}
                </div>
                <div class="text-green-700 text-sm font-medium mt-1">
                    المحقق حتى الآن
                </div>
            </div>
            
            <!-- النسبة المئوية -->
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4 text-center">
                <div class="text-blue-600 text-2xl font-bold">
                    {{ number_format($targetPercentage, 1) }}%
                </div>
                <div class="text-blue-700 text-sm font-medium mt-1">
                    نسبة الإنجاز
                </div>
            </div>
        </div>
        
        <!-- شريط التقدم -->
        <div class="mt-4">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>التقدم نحو الهدف</span>
                <span>{{ number_format($targetPercentage, 1) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-orange-400 to-orange-500 rounded-full transition-all duration-500 ease-out" 
                     style="width: {{ $targetPercentage }}%"></div>
            </div>
            @if($targetPercentage >= 100)
                <div class="text-green-600 text-sm font-medium mt-2 flex items-center gap-1">
                    <i class="fas fa-check-circle"></i>
                    تهانينا! لقد حققت هدفك الشهري
                </div>
            @else
                <div class="text-gray-600 text-sm mt-2">
                    المتبقي: ${{ number_format($userTarget - $userMonthlySales, 0) }}
                </div>
            @endif
        </div>
    </div>
    @endif
                           
    <!-- كارت واحد يحتوي على أزرار التبديل وجداول الإحصائيات -->
    <div class="flex flex-col items-center min-h-[300px] my-8">
        <div class="bg-gradient-to-tr from-primary-50 to-white rounded-3xl shadow-2xl p-6 w-full max-w-full border border-gray-100 backdrop-blur-md">
            <div class="flex items-center justify-center mb-8 gap-3">
                <!-- أيقونة فقط بدون دائرة بيضاء -->
                <i class="fas fa-chart-bar text-3xl text-primary-500 mr-2"></i>
                <h2 class="text-3xl font-extrabold text-primary-700 tracking-tight drop-shadow-lg">إحصائيات المبيعات</h2>
            </div>
            <!-- أزرار التبديل -->
            <div class="flex justify-center gap-2 sm:gap-4 my-4 flex-wrap">
                <button wire:click="updateStatsViewType('monthly')"
                        class="px-3 py-2 rounded-lg border focus:outline-none transition font-bold w-full sm:w-auto text-xs sm:text-base mb-2 sm:mb-0"
                        @if($statsViewType === 'monthly') style="background: rgb(var(--primary-500)); color: white;" @else style="background: white; color: rgb(var(--primary-500)); border: 1px solid rgb(var(--primary-500));" @endif>
                    شهرياً
                </button>
                <button wire:click="updateStatsViewType('service')"
                        class="px-3 py-2 rounded-lg border focus:outline-none transition font-bold w-full sm:w-auto text-xs sm:text-base mb-2 sm:mb-0"
                        @if($statsViewType === 'service') style="background: rgb(var(--primary-500)); color: white;" @else style="background: white; color: rgb(var(--primary-500)); border: 1px solid rgb(var(--primary-500));" @endif>
                    حسب الخدمة
                </button>
                <button wire:click="updateStatsViewType('employee')"
                        class="px-3 py-2 rounded-lg border focus:outline-none transition font-bold w-full sm:w-auto text-xs sm:text-base mb-2 sm:mb-0"
                        @if($statsViewType === 'employee') style="background: rgb(var(--primary-500)); color: white;" @else style="background: white; color: rgb(var(--primary-500)); border: 1px solid rgb(var(--primary-500));" @endif>
                    الموظف
                </button>
                <button wire:click="updateStatsViewType('branch')"
                        class="px-3 py-2 rounded-lg border focus:outline-none transition font-bold w-full sm:w-auto text-xs sm:text-base mb-2 sm:mb-0"
                        @if($statsViewType === 'branch') style="background: rgb(var(--primary-500)); color: white;" @else style="background: white; color: rgb(var(--primary-500)); border: 1px solid rgb(var(--primary-500));" @endif>
                    الفرع
                </button>
            </div>

            <!-- الجداول -->
            <div class="flex flex-col md:flex-row gap-8 md:gap-10">
                <div class="flex-1 overflow-x-auto bg-white/80 rounded-2xl shadow-lg p-4" style="min-height: 350px; max-height: 400px; overflow-y: auto;">
@if($statsViewType === 'monthly')
                    <table class="min-w-full text-center border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-gradient-to-r from-primary-100 to-primary-50 text-primary-700">
                                <th class="px-4 py-2 rounded-r-xl">السنة</th>
                                <th class="px-4 py-2">الشهر</th>
                                <th class="px-4 py-2">عدد العمليات</th>
                                <th class="px-4 py-2">المبيعات المُحصّلة</th>
                                <th class="px-4 py-2">المبيعات غير المُحصّلة</th>
                                <th class="px-4 py-2 rounded-l-xl">إجمالي المبيعات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sumOps=0; $sumCollected=0; $sumPending=0; $sumTotal=0; @endphp
                            @forelse($salesByMonth as $row)
                                @php
                                    $ops = (int)($row['operations_count'] ?? 0);
                                    $col = (float)($row['collected_sales'] ?? 0);
                                    $pen = (float)($row['pending_sales'] ?? 0);
                                    $tot = (float)($row['total_sales'] ?? 0);
                                    $sumOps += $ops; $sumCollected += $col; $sumPending += $pen; $sumTotal += $tot;
                                @endphp
                                <tr class="bg-white hover:bg-primary-50 transition rounded-xl shadow-sm border border-gray-100">
                                    <td class="px-4 py-2 font-semibold text-gray-700">{{ $row['year'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['month'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $ops }}</td>
                                    <td class="px-4 py-2 font-bold text-green-700">{{ number_format($col, 2) }}</td>
                                    <td class="px-4 py-2 font-bold text-amber-700">{{ number_format($pen, 2) }}</td>
                                    <td class="px-4 py-2 font-bold text-primary-600">{{ number_format($tot, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-gray-400 py-4">لا توجد بيانات مبيعات متاحة</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50 text-primary-800 font-bold">
                                <td class="px-4 py-2 rounded-b-xl" colspan="2">الإجمالي</td>
                                <td class="px-4 py-2">{{ $sumOps }}</td>
                                <td class="px-4 py-2">{{ number_format($sumCollected, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($sumPending, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($sumTotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

@elseif($statsViewType === 'service')
                    <table class="min-w-full text-center border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-gradient-to-r from-primary-100 to-primary-50 text-primary-700">
                                <th class="px-4 py-2 rounded-r-xl">الخدمة</th>
                                <th class="px-4 py-2">عدد العمليات</th>
                                <th class="px-4 py-2">المبيعات المُحصّلة</th>
                                <th class="px-4 py-2">المبيعات غير المُحصّلة</th>
                                <th class="px-4 py-2 rounded-l-xl">إجمالي المبيعات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sumOps=0; $sumCollected=0; $sumPending=0; $sumTotal=0; @endphp
                            @forelse($salesByService as $row)
                                @php
                                    $ops = (int)($row['operations_count'] ?? 0);
                                    $col = (float)($row['collected_sales'] ?? 0);
                                    $pen = (float)($row['pending_sales'] ?? 0);
                                    $tot = (float)($row['total_sales'] ?? 0);
                                    $sumOps += $ops; $sumCollected += $col; $sumPending += $pen; $sumTotal += $tot;
                                @endphp
                                <tr class="bg-white hover:bg-primary-50 transition rounded-xl shadow-sm border border-gray-100">
                                    <td class="px-4 py-2 font-semibold text-gray-700">{{ $row['service_type'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $ops }}</td>
                                    <td class="px-4 py-2 font-bold text-green-700">{{ number_format($col, 2) }}</td>
                                    <td class="px-4 py-2 font-bold text-amber-700">{{ number_format($pen, 2) }}</td>
                                    <td class="px-4 py-2 font-bold text-primary-600">{{ number_format($tot, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-gray-400 py-4">لا توجد بيانات مبيعات متاحة</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50 text-primary-800 font-bold">
                                <td class="px-4 py-2 rounded-b-xl">الإجمالي</td>
                                <td class="px-4 py-2">{{ $sumOps }}</td>
                                <td class="px-4 py-2">{{ number_format($sumCollected, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($sumPending, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($sumTotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

@elseif($statsViewType === 'employee')
                    <table class="min-w-full text-center border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-gradient-to-r from-primary-100 to-primary-50 text-primary-700">
                                <th class="px-4 py-2 rounded-r-xl">الموظف</th>
                                <th class="px-4 py-2">عدد العمليات</th>
                                <th class="px-4 py-2">المبيعات المُحصّلة</th>
                                <th class="px-4 py-2">المبيعات غير المُحصّلة</th>
                                <th class="px-4 py-2 rounded-l-xl">إجمالي المبيعات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sumOps=0; $sumCollected=0; $sumPending=0; $sumTotal=0; @endphp
                            @forelse($salesByMonth as $row)
                                @php
                                    $ops = (int)($row['operations_count'] ?? 0);
                                    $col = (float)($row['collected_sales'] ?? 0);
                                    $pen = (float)($row['pending_sales'] ?? 0);
                                    $tot = (float)($row['total_sales'] ?? 0);
                                    $sumOps += $ops; $sumCollected += $col; $sumPending += $pen; $sumTotal += $tot;
                                @endphp
                                <tr class="bg-white hover:bg-primary-50 transition rounded-xl shadow-sm border border-gray-100">
                                    <td class="px-4 py-2 text-gray-700">{{ $row['employee'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $ops }}</td>
                                    <td class="px-4 py-2 font-bold text-green-700">{{ number_format($col, 2) }}</td>
                                    <td class="px-4 py-2 font-bold text-amber-700">{{ number_format($pen, 2) }}</td>
                                    <td class="px-4 py-2 font-bold text-primary-600">{{ number_format($tot, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-gray-400 py-4">لا توجد بيانات مبيعات متاحة</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50 text-primary-800 font-bold">
                                <td class="px-4 py-2 rounded-b-xl">الإجمالي</td>
                                <td class="px-4 py-2">{{ $sumOps }}</td>
                                <td class="px-4 py-2">{{ number_format($sumCollected, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($sumPending, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($sumTotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

@elseif($statsViewType === 'branch')
                    <table class="min-w-full text-center border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-gradient-to-r from-primary-100 to-primary-50 text-primary-700">
                                <th class="px-4 py-2 rounded-r-xl">الفرع</th>
                                <th class="px-4 py-2">عدد العمليات</th>
                                <th class="px-4 py-2">المبيعات المُحصّلة</th>
                                <th class="px-4 py-2">المبيعات غير المُحصّلة</th>
                                <th class="px-4 py-2 rounded-l-xl">إجمالي المبيعات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sumOps=0; $sumCollected=0; $sumPending=0; $sumTotal=0; @endphp
                            @forelse($salesByMonth as $row)
                                @php
                                    $ops = (int)($row['operations_count'] ?? 0);
                                    $col = (float)($row['collected_sales'] ?? 0);
                                    $pen = (float)($row['pending_sales'] ?? 0);
                                    $tot = (float)($row['total_sales'] ?? 0);
                                    $sumOps += $ops; $sumCollected += $col; $sumPending += $pen; $sumTotal += $tot;
                                @endphp
                                <tr class="bg-white hover:bg-primary-50 transition rounded-xl shadow-sm border border-gray-100">
                                    <td class="px-4 py-2 text-gray-700">{{ $row['branch'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $ops }}</td>
                                    <td class="px-4 py-2 font-bold text-green-700">{{ number_format($col, 2) }}</td>
                                    <td class="px-4 py-2 font-bold text-amber-700">{{ number_format($pen, 2) }}</td>
                                    <td class="px-4 py-2 font-bold text-primary-600">{{ number_format($tot, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-gray-400 py-4">لا توجد بيانات مبيعات متاحة</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50 text-primary-800 font-bold">
                                <td class="px-4 py-2 rounded-b-xl">الإجمالي</td>
                                <td class="px-4 py-2">{{ $sumOps }}</td>
                                <td class="px-4 py-2">{{ number_format($sumCollected, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($sumPending, 2) }}</td>
                                <td class="px-4 py-2">{{ number_format($sumTotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
@endif
                </div>

                <!-- كروت المبيعات المحققة والأرباح والتكاليف -->
                <div class="w-full md:w-80 flex flex-col gap-4 md:gap-2 ">
                    <!-- المبيعات المحققة / الهدف -->
                    <div class="bg-primary-50 rounded-xl p-3 md:p-5 shadow-sm border border-primary-200 text-center w-full">
                        <div class="flex items-center justify-center gap-2 mb-1">
                            <i class="fas fa-bullseye text-primary-600"></i>
                            <span class="font-bold text-primary-700">المبيعات المحققة / الهدف</span>
                        </div>
                        <div class="text-2xl font-extrabold text-primary-700">
                            {{ number_format($monthlyAchieved, 2) }} / {{ number_format($monthlyTarget, 2) }}
                        </div>

                        <!-- كروت صغيرة أسفل الهدف -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6">
                            <!-- المدفوع -->
                            <div class="rounded-xl bg-green-50 px-3 py-2 text-center shadow-sm border border-green-100">
                                <div class="text-xs text-green-700 font-semibold flex justify-center items-center gap-1 mb-1">
                                    <i class="fas fa-hand-holding-usd"></i> المدفوع مباشرة
                                </div>
                                <div class="text-base font-bold text-green-800">
                                    {{ number_format($monthlyPaid, 2) }}
                                </div>
                            </div>

                            <!-- المحصل -->
                            <div class="rounded-xl bg-blue-50 px-3 py-2 text-center shadow-sm border border-blue-100">
                                <div class="text-xs text-blue-700 font-semibold flex justify-center items-center gap-1 mb-1">
                                    <i class="fas fa-wallet"></i> المبالغ المحصلة
                                </div>
                                <div class="text-base font-bold text-blue-800">
                                    {{ number_format($monthlyCollected, 2) }}
                                </div>
                            </div>

                            <!-- المؤجل -->
                            <div class="rounded-xl bg-gray-50 px-3 py-2 text-center shadow-sm border border-gray-100">
                                <div class="text-xs text-gray-700 font-semibold flex justify-center items-center gap-1 mb-1">
                                    <i class="fas fa-clock"></i> المبالغ المؤجلة
                                </div>
                                <div class="text-base font-bold text-gray-800">
                                    {{ number_format($monthlyRemaining, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الأرباح -->
                    <div class="bg-green-50 rounded-xl p-3 md:p-5 shadow-sm text-center w-full">
                        <div class="flex items-center justify-center gap-2 mb-1">
                            <i class="fas fa-coins text-green-600"></i>
                            <span class="font-bold text-green-700">أرباح هذا الشهر</span>
                        </div>
                        <div class="text-2xl font-extrabold text-green-700">
                            {{ number_format($monthlyProfit, 2) }}
                        </div>
                    </div>
                    <!-- التكاليف -->
                    <div class="bg-red-50 rounded-xl p-3 md:p-5 shadow-sm text-center w-full">
                        <div class="flex items-center justify-center gap-2 mb-1">
                            <i class="fas fa-money-bill-wave text-red-600"></i>
                            <span class="font-bold text-red-700">إجمالي التكاليف</span>
                        </div>
                        <div class="text-2xl font-extrabold text-red-700">
                            {{ number_format($monthlyCost, 2) }}
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    @php
        $showUsersState = Auth::user()->hasRole('agency-admin') || Auth::user()->can('employees.view') || Auth::user()->can('users.view');
    @endphp
    @if($showUsersState)
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 my-8 w-full max-w-2xl mx-auto">
        <div style="background:rgba(var(--primary-100),0.15); border:1px solid rgb(var(--primary-200));" class="rounded-xl p-4 shadow-sm flex flex-col items-center text-center">
            <i class="fas fa-signal text-[20px] mb-1" style="color:rgb(var(--primary-700));"></i>
            <div class="text-xl font-extrabold" style="color:rgb(var(--primary-700));">{{ $onlineUsers }}</div>
            <div class="text-sm mt-1" style="color:rgb(var(--primary-700));">المستخدمون المتصلون الآن</div>
        </div>
        <div style="background:rgba(var(--primary-100),0.15); border:1px solid rgb(var(--primary-200));" class="rounded-xl p-4 shadow-sm flex flex-col items-center text-center">
            <i class="fas fa-users text-[20px] mb-1" style="color:rgb(var(--primary-700));"></i>
            <div class="text-xl font-extrabold" style="color:rgb(var(--primary-700));">{{ $totalUsers }}</div>
            <div class="text-sm mt-1" style="color:rgb(var(--primary-700));">إجمالي المستخدمين</div>
        </div>
        <div style="background:rgba(var(--primary-100),0.15); border:1px solid rgb(var(--primary-200));" class="rounded-xl p-4 shadow-sm flex flex-col items-center text-center">
            <i class="fas fa-user-check text-[20px] mb-1" style="color:rgb(var(--primary-700));"></i>
            <div class="text-xl font-extrabold" style="color:rgb(var(--primary-700));">{{ $activeUsers }}</div>
            <div class="text-sm mt-1" style="color:rgb(var(--primary-700));">المستخدمون النشطون</div>
        </div>
    </div>
    @endif

    <!-- تم حذف كروت وعدد الأدوار وعدد الصلاحيات من الإحصائيات العلوية -->
    <!-- تم حذف أقسام آخر المستخدمين، آخر الأدوار، وآخر الصلاحيات من لوحة التحكم -->
</div>

@push('scripts')
    @if(isset($this->monthlyChart))
        {!! $this->monthlyChart->script() !!}
    @endif
@endpush
