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
    <!-- كروت إحصائيات المستخدمين -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 my-6">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-2xl font-bold text-primary-600">{{ $onlineUsers }}</div>
            <div class="text-sm text-gray-600 mt-2">المستخدمون المتصلون الآن</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-2xl font-bold text-primary-600">{{ $totalUsers }}</div>
            <div class="text-sm text-gray-600 mt-2">إجمالي المستخدمين</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-2xl font-bold text-primary-600">{{ $activeUsers }}</div>
            <div class="text-sm text-gray-600 mt-2">المستخدمون النشطون</div>
        </div>
    </div>
    <!-- تم حذف كروت وعدد الأدوار وعدد الصلاحيات من الإحصائيات العلوية -->

    <!-- تم حذف أقسام آخر المستخدمين، آخر الأدوار، وآخر الصلاحيات من لوحة التحكم -->
</div>
@push('scripts')
    @if(isset($this->monthlyChart))
        {!! $this->monthlyChart->script() !!}
    @endif
@endpush