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
            <div class="md:order-2 order-1 flex justify-center w-full md:w-auto my-4 md:my-0">
                    <div class="grid grid-cols-8 gap-4">
                        @php
                            $quickLinks = [
                                ['route' => 'agency.users', 'icon' => 'users', 'title' => 'المستخدمين', 'desc' => 'إدارة مستخدمي الوكالة'],
                                ['route' => 'agency.roles', 'icon' => 'user-tag', 'title' => 'الأدوار', 'desc' => 'إدارة أدوار المستخدمين'],
                                ['route' => 'agency.service_types', 'icon' => 'concierge-bell', 'title' => 'الخدمات', 'desc' => 'إدارة أنواع الخدمات'],
                                ['route' => 'agency.providers', 'icon' => 'briefcase', 'title' => 'المزودين', 'desc' => 'إدارة مزودي الخدمات'],
                                ['route' => 'agency.customers.add', 'icon' => 'user-plus', 'title' => 'إضافة عميل', 'desc' => 'إضافة عميل جديد'],
                                ['route' => 'agency.hr.employees.index', 'icon' => 'user-tie', 'title' => 'الموظفين', 'desc' => 'إدارة موظفي الوكالة'],
                                ['route' => 'agency.sales.index', 'icon' => 'chart-line', 'title' => 'المبيعات', 'desc' => 'تقارير وإحصائيات المبيعات'],
                                ['route' => 'agency.profile', 'icon' => 'user-circle', 'title' => 'الملف الشخصي', 'desc' => 'إدارة ملفك الشخصي'],
                            ];
                        @endphp
                        @foreach($quickLinks as $link)
                            <a href="{{ route($link['route']) }}"
                               class="relative flex items-center justify-center w-16 h-16 rounded-xl shadow transition group border border-gray-100 bg-[rgb(var(--primary-500))] group-hover:bg-white"
                               style="font-size: 2rem;">
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
                    <div>
                        <p class="text-white text-sm">رقم الترخيص: <span class="font-bold">{{ $this->agencyInfo->license_number }}</span></p>
                        <p class="text-white/80 text-xs mt-1">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            ينتهي في {{ $this->agencyInfo->license_expiry_date->format('Y-m-d') }}
                        </p>
                    </div>
                    <div class="text-right">
                        @php
                            $expiry = $this->agencyInfo->license_expiry_date;
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
        </div>
    </div>
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stats Cards -->
        @php
            $mainStatCards = [
                ['icon' => 'users', 'title' => 'إجمالي المستخدمين', 'value' => $stats['total_users'], 'color' => 'primary'],
                ['icon' => 'user-check', 'title' => 'المستخدمين النشطين', 'value' => $stats['active_users'], 'color' => 'green'],
                ['icon' => 'user-tag', 'title' => 'عدد الأدوار', 'value' => $stats['roles_count'], 'color' => 'indigo'],
                ['icon' => 'shield-alt', 'title' => 'عدد الصلاحيات', 'value' => $stats['permissions_count'], 'color' => 'purple'],
            ];
        @endphp

        @foreach($mainStatCards as $card)
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-start justify-between">
                    @if($card['title'] !== 'إجمالي المستخدمين')
    <div class="p-3 rounded-lg mr-3"
         style="background-color: rgba(var(--{{ $card['color'] }}-100), 0.5); color: rgb(var(--{{ $card['color'] }}-600));">
        <i class="fas fa-{{ $card['icon'] }} text-lg"></i>
    </div>
@endif

                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">{{ $card['title'] }}</p>
                        <p class="text-2xl font-bold mt-1" style="color: rgb(var(--{{ $card['color'] }}-600));">
                            {{ $card['value'] }}
                        </p>
                    </div>
                </div>
                <div class="mt-3 h-1 rounded-full overflow-hidden bg-gray-100">
                    <div class="h-full" style="width: 100%; background-color: rgb(var(--{{ $card['color'] }}-500));"></div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- أزرار التبديل بين طرق العرض -->
    <div class="flex justify-center gap-4 my-4">
        <button wire:click="updateStatsViewType('monthly')"
                class="px-4 py-2 rounded-lg border focus:outline-none transition font-bold"
                @if($statsViewType === 'monthly') style="background: rgb(var(--primary-500)); color: white;" @else style="background: white; color: rgb(var(--primary-500)); border: 1px solid rgb(var(--primary-500));" @endif>
            شهرياً
        </button>
        <button wire:click="updateStatsViewType('service')"
                class="px-4 py-2 rounded-lg border focus:outline-none transition font-bold"
                @if($statsViewType === 'service') style="background: rgb(var(--primary-500)); color: white;" @else style="background: white; color: rgb(var(--primary-500)); border: 1px solid rgb(var(--primary-500));" @endif>
            حسب الخدمة
        </button>
        <button wire:click="updateStatsViewType('employee')"
                class="px-4 py-2 rounded-lg border focus:outline-none transition font-bold"
                @if($statsViewType === 'employee') style="background: rgb(var(--primary-500)); color: white;" @else style="background: white; color: rgb(var(--primary-500)); border: 1px solid rgb(var(--primary-500));" @endif>
            الموظف
        </button>
        <button wire:click="updateStatsViewType('branch')"
                class="px-4 py-2 rounded-lg border focus:outline-none transition font-bold"
                @if($statsViewType === 'branch') style="background: rgb(var(--primary-500)); color: white;" @else style="background: white; color: rgb(var(--primary-500)); border: 1px solid rgb(var(--primary-500));" @endif>
            الفرع
        </button>
    </div>

    @if($statsViewType === 'monthly')
        <div class="flex flex-col items-center min-h-[300px] my-8">
            <div class="bg-white rounded-2xl shadow-lg p-4 w-full max-w-xl border border-gray-100">
                <div class="flex items-center justify-center mb-6 gap-2">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-tr from-primary-500 to-primary-400 text-white shadow">
                        <i class="fas fa-chart-bar text-2xl"></i>
                    </span>
                    <div>
                        <h2 class="text-2xl font-extrabold text-gray-800 tracking-tight">إحصائيات المبيعات حسب الشهر</h2>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-center border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-gradient-to-r from-primary-100 to-primary-50 text-primary-700">
                                <th class="px-4 py-2 rounded-r-xl">السنة</th>
                                <th class="px-4 py-2">الشهر</th>
                                <th class="px-4 py-2">عدد العمليات</th>
                                <th class="px-4 py-2 rounded-l-xl">إجمالي المبيعات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalOperations = 0;
                                $totalSales = 0;
                            @endphp
                            @forelse($salesByMonth as $row)
                                @php
                                    $totalOperations += $row['operations_count'] ?? 0;
                                    $totalSales += $row['total_sales'] ?? 0;
                                @endphp
                                <tr class="bg-white hover:bg-primary-50 transition rounded-xl shadow-sm border border-gray-100">
                                    <td class="px-4 py-2 font-semibold text-gray-700">{{ $row['year'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['month'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['operations_count'] ?? '-' }}</td>
                                    <td class="px-4 py-2 font-bold text-primary-600">{{ number_format($row['total_sales'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-gray-400 py-4">لا توجد بيانات مبيعات متاحة</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50 text-primary-800 font-bold">
                                <td class="px-4 py-2 rounded-b-xl" colspan="2">الإجمالي</td>
                                <td class="px-4 py-2">{{ $totalOperations }}</td>
                                <td class="px-4 py-2">{{ number_format($totalSales, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @elseif($statsViewType === 'service')
        <div class="flex flex-col items-center min-h-[300px] my-8">
            <div class="bg-white rounded-2xl shadow-lg p-4 w-full max-w-xl border border-gray-100">
                <div class="flex items-center justify-center mb-6 gap-2">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-tr from-primary-500 to-primary-400 text-white shadow">
                        <i class="fas fa-chart-pie text-2xl"></i>
                    </span>
                    <h2 class="text-2xl font-extrabold text-gray-800 tracking-tight">إحصائيات المبيعات حسب الخدمة</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-center border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-gradient-to-r from-primary-100 to-primary-50 text-primary-700">
                                <th class="px-4 py-2 rounded-r-xl">الخدمة</th>
                                <th class="px-4 py-2">عدد العمليات</th>
                                <th class="px-4 py-2 rounded-l-xl">إجمالي المبيعات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalSales = 0; $totalOperations = 0; @endphp
                            @forelse($salesByMonth as $row)
                                @php $totalSales += $row['total_sales'] ?? 0; $totalOperations += $row['operations_count'] ?? 0; @endphp
                                <tr class="bg-white hover:bg-primary-50 transition rounded-xl shadow-sm border border-gray-100">
                                    <td class="px-4 py-2 text-gray-700">{{ $row['service_type'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['operations_count'] ?? '-' }}</td>
                                    <td class="px-4 py-2 font-bold text-primary-600">{{ number_format($row['total_sales'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-gray-400 py-4">لا توجد بيانات مبيعات متاحة</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50 text-primary-800 font-bold">
                                <td class="px-4 py-2 rounded-b-xl">الإجمالي</td>
                                <td class="px-4 py-2">{{ $totalOperations }}</td>
                                <td class="px-4 py-2">{{ number_format($totalSales, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @elseif($statsViewType === 'employee')
        <div class="flex flex-col items-center min-h-[300px] my-8">
            <div class="bg-white rounded-2xl shadow-lg p-4 w-full max-w-xl border border-gray-100">
                <div class="flex items-center justify-center mb-6 gap-2">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-tr from-primary-500 to-primary-400 text-white shadow">
                        <i class="fas fa-user text-2xl"></i>
                    </span>
                    <h2 class="text-2xl font-extrabold text-gray-800 tracking-tight">إحصائيات المبيعات حسب الموظف</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-center border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-gradient-to-r from-primary-100 to-primary-50 text-primary-700">
                                <th class="px-4 py-2 rounded-r-xl">الموظف</th>
                                <th class="px-4 py-2">عدد العمليات</th>
                                <th class="px-4 py-2 rounded-l-xl">إجمالي المبيعات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalSales = 0; $totalOperations = 0; @endphp
                            @forelse($salesByMonth as $row)
                                @php $totalSales += $row['total_sales'] ?? 0; $totalOperations += $row['operations_count'] ?? 0; @endphp
                                <tr class="bg-white hover:bg-primary-50 transition rounded-xl shadow-sm border border-gray-100">
                                    <td class="px-4 py-2 text-gray-700">{{ $row['employee'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['operations_count'] ?? '-' }}</td>
                                    <td class="px-4 py-2 font-bold text-primary-600">{{ number_format($row['total_sales'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-gray-400 py-4">لا توجد بيانات مبيعات متاحة</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50 text-primary-800 font-bold">
                                <td class="px-4 py-2 rounded-b-xl">الإجمالي</td>
                                <td class="px-4 py-2">{{ $totalOperations }}</td>
                                <td class="px-4 py-2">{{ number_format($totalSales, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @elseif($statsViewType === 'branch')
        <div class="flex flex-col items-center min-h-[300px] my-8">
            <div class="bg-white rounded-2xl shadow-lg p-4 w-full max-w-xl border border-gray-100">
                <div class="flex items-center justify-center mb-6 gap-2">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-tr from-primary-500 to-primary-400 text-white shadow">
                        <i class="fas fa-code-branch text-2xl"></i>
                    </span>
                    <h2 class="text-2xl font-extrabold text-gray-800 tracking-tight">إحصائيات المبيعات حسب الفرع</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-center border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-gradient-to-r from-primary-100 to-primary-50 text-primary-700">
                                <th class="px-4 py-2 rounded-r-xl">الفرع</th>
                                <th class="px-4 py-2">عدد العمليات</th>
                                <th class="px-4 py-2 rounded-l-xl">إجمالي المبيعات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalSales = 0; $totalOperations = 0; @endphp
                            @forelse($salesByMonth as $row)
                                @php $totalSales += $row['total_sales'] ?? 0; $totalOperations += $row['operations_count'] ?? 0; @endphp
                                <tr class="bg-white hover:bg-primary-50 transition rounded-xl shadow-sm border border-gray-100">
                                    <td class="px-4 py-2 text-gray-700">{{ $row['branch'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['operations_count'] ?? '-' }}</td>
                                    <td class="px-4 py-2 font-bold text-primary-600">{{ number_format($row['total_sales'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-gray-400 py-4">لا توجد بيانات مبيعات متاحة</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50 text-primary-800 font-bold">
                                <td class="px-4 py-2 rounded-b-xl">الإجمالي</td>
                                <td class="px-4 py-2">{{ $totalOperations }}</td>
                                <td class="px-4 py-2">{{ number_format($totalSales, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Recent Data Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Users -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 col-span-1">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">آخر المستخدمين</h2>
                <a href="{{ route('agency.users') }}" class="text-sm font-medium px-3 py-1 rounded-lg hover:bg-gray-50"
                   style="color: rgb(var(--primary-500));">
                    عرض الكل
                </a>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($this->recentUsers as $user)
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3"
                                 style="background-color: rgba(var(--primary-500), 0.1); color: rgb(var(--primary-500));">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                            </div>
                            <div class="ml-2">
                                @if($user->roles->first())
                                    @if($user->roles->first()->name === 'مدير')
                                        <span class="text-xs px-2 py-1 rounded-full" style="background-color: rgba(var(--primary-500), 0.1); color: rgb(var(--primary-500));">
                                            {{ $user->roles->first()->name }}
                                        </span>
                                    @elseif($user->roles->first()->name === 'موظف')
                                        <span class="text-xs px-2 py-1 rounded-full" style="background-color: rgba(var(--primary-100), 0.1); color: rgb(var(--primary-100));">
                                            {{ $user->roles->first()->name }}
                                        </span>
                                    @else
                                        <span class="text-xs px-2 py-1 rounded-full" style="background-color: rgba(var(--primary-600), 0.1); color: rgb(var(--primary-600));">
                                            {{ $user->roles->first()->name }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                        غير محدد
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Roles -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 col-span-1">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">آخر الأدوار</h2>
                <a href="{{ route('agency.roles') }}" class="text-sm font-medium px-3 py-1 rounded-lg hover:bg-gray-50"
                   style="color: rgb(var(--primary-500));">
                    عرض الكل
                </a>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($this->recentRoles as $role)
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3"
                                 style="background-color: rgba(var(--primary-500), 0.1); color: rgb(var(--primary-500));">
                                {{ mb_substr($role->name, 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $role->name }}</p>
                                <p class="text-xs text-gray-500">{{ $role->created_at->format('Y-m-d') }}</p>
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $role->users_count }} مستخدم
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Permissions -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 col-span-1">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">آخر الصلاحيات</h2>
                <a href="{{ route('agency.permissions') }}" class="text-sm font-medium px-3 py-1 rounded-lg hover:bg-gray-50"
                   style="color: rgb(var(--primary-500));">
                    عرض الكل
                </a>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($this->recentPermissions as $permission)
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full flex items-center justify-center mr-3"
                                 style="background-color: rgba(var(--primary-500), 0.1); color: rgb(var(--primary-500));">
                                {{ mb_substr($permission->name, 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $permission->name }}</p>
                                <p class="text-xs text-gray-500">{{ $permission->created_at->format('Y-m-d') }}</p>
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $permission->roles_count }} دور
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>