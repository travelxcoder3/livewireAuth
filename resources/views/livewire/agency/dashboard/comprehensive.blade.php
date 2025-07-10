@php $stats = $this->comprehensiveStats; @endphp

<div class="space-y-6">
    <!-- Welcome Header with License Info -->
    <div class="rounded-xl p-6 mb-6" style="background: linear-gradient(135deg, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">مرحباً بك في {{ $this->agencyInfo->name }}</h1>
                <p class="text-white/90">
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
            <div class="mt-4 md:mt-0 bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-white text-sm">رقم الترخيص: <span class="font-bold">{{ $this->agencyInfo->license_number }}</span></p>
                        <p class="text-white/80 text-xs mt-1">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            ينتهي في {{ $this->agencyInfo->license_expiry_date->format('Y-m-d') }}
                        </p>
                    </div>
                    <div class="text-right">
                        @if($this->agencyInfo->license_expiry_date->isPast())
                            <span class="px-2 py-1 text-xs font-bold rounded-full bg-red-500/20 text-red-100">منتهي</span>
                        @elseif($this->agencyInfo->license_expiry_date->diffInDays(now()) <= 30)
                            <span class="px-2 py-1 text-xs font-bold rounded-full bg-amber-500/20 text-amber-100">
                                ينتهي خلال {{ $this->agencyInfo->license_expiry_date->diffInDays(now()) }} يوم
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-bold rounded-full bg-green-500/20 text-green-100">نشط</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">روابط سريعة</h2>
            <div class="text-sm" style="color: rgb(var(--primary-500));">إدارة النظام</div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @php
                $quickLinks = [
                    ['route' => 'agency.users', 'icon' => 'users', 'title' => 'المستخدمين', 'desc' => 'إدارة مستخدمي الوكالة'],
                    ['route' => 'agency.roles', 'icon' => 'user-tag', 'title' => 'الأدوار', 'desc' => 'إدارة أدوار المستخدمين'],
                    ['route' => 'agency.permissions', 'icon' => 'shield-alt', 'title' => 'الصلاحيات', 'desc' => 'إدارة صلاحيات النظام'],
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
                   class="group flex items-center gap-4 p-4 rounded-xl transition-all duration-200 hover:shadow-md border border-gray-100 hover:border-transparent"
                   style="background-color: rgba(var(--primary-50));">
                    <div class="flex-shrink-0 p-3 rounded-lg mr-4 transition-all duration-200 group-hover:scale-110"
                         style="background-color: rgba(var(--primary-500), 0.1); color: rgb(var(--primary-500));">
                        @switch($link['icon'])
                            @case('users')
                                <svg class="w-6 h-6 p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                @break
                            @case('user-tag')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                @break
                            @case('shield-alt')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                @break
                            @case('concierge-bell')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8V6a4 4 0 00-8 0v2m8 0v2a4 4 0 108 0V8m-8 0h8" />
                                </svg>
                                @break
                            @case('briefcase')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7V6a2 2 0 012-2h12a2 2 0 012 2v1M4 7h16v10H4V7z" />
                                </svg>
                                @break
                            @case('user-plus')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9h3m0 0v3m0-3l-3 3M7 11a4 4 0 110-8 4 4 0 010 8zm6 8v-2a4 4 0 00-8 0v2" />
                                </svg>
                                @break
                            @case('user-tie')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12c2.28 0 4-1.72 4-4s-1.72-4-4-4-4 1.72-4 4 1.72 4 4 4zM6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2" />
                                </svg>
                                @break
                            @case('chart-line')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M9 17l3-3 4 4M13 13l3-3" />
                                </svg>
                                @break
                            @case('user-circle')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A8.966 8.966 0 0112 15c2.072 0 3.978.707 5.465 1.889M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.071 4.929a10 10 0 11-14.142 0 10 10 0 0114.142 0z" />
                                </svg>
                                @break
                            @default
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 6v6h6" />
                                </svg>
                        @endswitch
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 group-hover:text-gray-900">{{ $link['title'] }}</h3>
                        <p class="text-sm text-gray-600 group-hover:text-gray-700">{{ $link['desc'] }}</p>
                    </div>
                    <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                         style="color: rgb(var(--primary-500));">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                </a>
            @endforeach
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