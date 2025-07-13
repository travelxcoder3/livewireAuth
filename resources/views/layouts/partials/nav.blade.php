@php
    $showPoliciesLink = Auth::user()->hasRole('agency-admin') || Auth::user()->can('policies.view');
    $showPoliciesLinkUser = Auth::check() && Auth::user()->agency_id !== null && !Auth::user()->hasRole('agency-admin');
@endphp 
<nav class="w-full flex items-center justify-between px-6 shadow-sm rounded-t-2xl nav-gradient"
    style="padding-top: 8px; padding-bottom: 8px; min-height:48px;">

<!-- شعار الوكالة واسمها -->
<div class="flex items-center gap-3">
    @if(Auth::user()->agency?->logo)
        <img src="{{ asset('storage/' . Auth::user()->agency->logo) }}"
             alt="شعار الوكالة"
             class="h-9 w-9 rounded-full object-cover shadow-md" />
    @else
        <svg class="h-9 w-9" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="8" width="28" height="20" rx="6" fill="rgb(var(--primary-500))" />
            <rect x="8" y="14" width="4" height="4" rx="1" fill="#fff" />
            <rect x="14" y="14" width="4" height="4" rx="1" fill="#fff" />
            <rect x="20" y="14" width="4" height="4" rx="1" fill="#fff" />
            <rect x="12" y="22" width="8" height="4" rx="2" fill="rgb(var(--primary-600))" />
        </svg>
    @endif

    <span class="text-white text-lg font-bold tracking-tight">
        {{ Auth::user()->agency->name ?? 'Travel X' }}
    </span>
</div>

    <!-- أزرار التنقل -->
    <div class="flex items-center justify-center flex-1 gap-2">
        <!-- الرئيسية -->
        <div
            class="relative group nav-item flex items-center px-2 py-1 rounded-full {{ request()->routeIs('agency.dashboard') ? 'active' : '' }}">
            <a href="{{ route('agency.dashboard') }}" class="flex items-center">
                <span class="nav-icon">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h3m10-11v11a1 1 0 01-1 1h-3m-4 0h4" />
                    </svg>
                </span>
                <span class="nav-text text-xs text-white whitespace-nowrap mr-2">الرئيسية</span>
            </a>
        </div>



        <!-- المبيعات -->
        @php
            $showSalesDropdown = Auth::user()->hasRole('agency-admin')
                || Auth::user()->can('sales.view')
                || Auth::user()->can('collections.view')
                || Auth::user()->can('invoices.view');
        @endphp
        @if($showSalesDropdown)
        <div class="relative group nav-item flex items-center px-2 py-1 rounded-full">
            <a href="#" class="flex items-center">
                <span class="nav-icon">
                    <i class="fas fa-cash-register text-white"></i>
                </span>
                <span class="nav-text text-xs text-white whitespace-nowrap mr-2">المبيعات</span>
            </a>
            <div class="dropdown-sales absolute right-0 top-full mt-2 min-w-[200px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('sales.view'))
                <a href="{{ route('agency.sales.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i>
                    إنشاء طلبات
                </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('collections.view'))
                <a href="{{ route('agency.collections') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-hand-holding-usd"></i>
                    التحصيل
                </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('invoices.view'))
                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-file-invoice"></i>
                    الفواتير
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- تهيئة إعدادات الشركة -->
        @php
            $showSettingsDropdown = Auth::user()->hasRole('agency-admin')
                || Auth::user()->can('profile.view')
                || Auth::user()->can('lists.view')
                || Auth::user()->can('invoice-setup.view')
                || Auth::user()->can('sequences.view')
                || Auth::user()->can('commissions-setup.view');
        @endphp
        @if($showSettingsDropdown)
        <div class="relative group nav-item flex items-center px-2 py-1 rounded-full">
            <a href="#" class="flex items-center">
                <span class="nav-icon">
                    <i class="fas fa-cogs text-white"></i>
                </span>
                <span class="nav-text text-xs text-white whitespace-nowrap mr-2">تهيئة إعدادات الشركة</span>
            </a>
            <div class="dropdown-settings absolute right-0 top-full mt-2 min-w-[220px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('profile.view'))
                <a href="{{ route('agency.profile') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-building"></i>
                    بيانات الشركة
                </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('lists.view'))
                <a href="{{ route('agency.dynamic-lists') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-list-alt"></i>
                    تهيئة القوائم
                </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('invoice-setup.view'))
                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-file-invoice-dollar"></i>
                    تهيئة الفواتير
                </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('sequences.view'))
                <a href="{{ route('agency.approval-sequences') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-random"></i>
                    تسلسل الموافقات
                </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('commissions-setup.view'))
                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-percentage"></i>
                    تهيئة العمولات
                </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin'))
                <a href="{{ route('agency.policies') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-file-contract"></i>
                    سياسات الوكالة
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- الخدمات -->
        <!-- @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('service_types.view'))
            <div
                class="relative group nav-item flex items-center px-2 py-1 rounded-full {{ request()->routeIs('agency.services') ? 'active' : '' }}">
                <a href="{{ route('agency.service_types') }}" class="flex items-center">
                    <span class="nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6M9 16h6M13 8H9m10-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2z" />
                        </svg>

                    </span>
                    <span class="nav-text text-xs text-white whitespace-nowrap mr-2">الخدمات</span>
                </a>
                <div
                    class="absolute right-0 top-full mt-2 min-w-[180px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block">
                    <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">جميع الخدمات</a>
                    <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">إضافة خدمة</a>
                    <div class="relative group/sub">
                        <a href="#"
                            class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex justify-between items-center">
                            أنواع الخدمات
                            <span class="ml-2 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-black" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </span>
                        </a>
                        <div
                            class="absolute right-full top-0 mt-0 min-w-[160px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover/sub:block">
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">خدمة 1</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">خدمة 2</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif --> 

        <!-- الموارد البشرية -->
        @php
            $showHRDropdown = Auth::user()->hasRole('agency-admin')
                || Auth::user()->can('employees.view')
                || Auth::user()->can('roles.view')
                || Auth::user()->can('users.view')
                || Auth::user()->can('permissions.view');
        @endphp
        @if($showHRDropdown)
        <div class="relative group nav-item flex items-center px-2 py-1 rounded-full">
            <a href="#" class="flex items-center">
                <span class="nav-icon bg-[rgb(var(--primary-500))] rounded-full p-1 shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2
                   M12 12a3 3 0 100-6 3 3 0 000 6zm-4 8v-5h8v5" />
                    </svg>
                </span>
                <span class="nav-text text-xs text-white whitespace-nowrap mr-2 hidden group-hover:inline-block transition-all duration-200">الموارد البشرية</span>
            </a>
            <div class="dropdown-users absolute right-0 top-full mt-2 min-w-[200px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('employees.view'))
                    <a href="{{ route('agency.hr.employees.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        الموظفين
                    </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('roles.view'))
                    <a href="{{ route('agency.roles') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                        <i class="fas fa-user-shield"></i>
                        الأدوار
                    </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('users.view'))
                    <a href="{{ route('agency.users') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                        <i class="fas fa-user"></i>
                        المستخدمين
                    </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('permissions.view'))
                    <a href="{{ route('agency.permissions') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                        <i class="fas fa-key"></i>
                        الصلاحيات
                    </a>
                @endif
            </div>
        </div>
        @endif

      

        <!-- الحسابات -->
        @php
            $showAccountsDropdown = Auth::user()->hasRole('agency-admin')
                || Auth::user()->can('financial-reports.view')
                || Auth::user()->can('customers.view')
                || Auth::user()->can('providers.view');
        @endphp
        @if($showAccountsDropdown)
        <div class="relative group nav-item flex items-center px-2 py-1 rounded-full">
            <a href="#" class="flex items-center">
                <span class="nav-icon">
                    <i class="fas fa-wallet text-white"></i>
                </span>
                <span class="nav-text text-xs text-white whitespace-nowrap mr-2">الحسابات</span>
            </a>
            <div class="dropdown-accounts absolute right-0 top-full mt-2 min-w-[200px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('accounts.view'))
                <a href="{{ route('agency.accounts') }}"" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-file-invoice-dollar"></i>
                    التقارير المالية
                </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('customers.view'))
                <a href="{{ route('agency.customers.add') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-folder-open"></i>
                    ملفات العملاء
                </a>
                @endif
                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('providers.view'))
                <a href="{{ route('agency.providers') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                    <i class="fas fa-briefcase"></i>
                    ملفات المزودين
                </a>
                @endif
            </div>
        </div>
        @endif

        

        <!--سياسات الشركة لمستخدمي الوكالة-->
        @if($showPoliciesLinkUser)
            <div class="nav-item flex items-center px-2 py-1 rounded-full {{ request()->routeIs('agency.policies.view') ? 'active' : '' }}">
                <a href="{{ route('agency.policies.view') }}" class="flex items-center">
                    <span class="nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16h8M8 12h6m-6-4h4M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
                        </svg>
                    </span>
                    <span class="nav-text text-xs text-white whitespace-nowrap mr-2">سياسات الوكالة</span>
                </a>
            </div>
        @endif
    

    <!-- أدوات التحكم (الثيم، اللغة، المستخدم) في أقصى الشريط فقط -->
    <div class="flex items-center gap-2 sm:gap-4">
        <x-theme-selector />
        <span class="flex items-center justify-center h-10 w-10 rounded-full bg-white/10">
            <svg class="h-6 w-6 rounded-full" viewBox="0 0 24 24">
                <rect width="24" height="24" fill="#fff" />
                <path d="M0 0h24v24H0z" fill="#00247d" />
                <path d="M0 0l24 24M24 0L0 24" stroke="#fff" stroke-width="2" />
                <path d="M0 0l24 24M24 0L0 24" stroke="#cf142b" stroke-width="1" />
                <rect x="10" width="4" height="24" fill="#fff" />
                <rect y="10" width="24" height="4" fill="#fff" />
                <rect x="11" width="2" height="24" fill="#cf142b" />
                <rect y="11" width="24" height="2" fill="#cf142b" />
            </svg>
        </span>
        <div class="relative group-user-dropdown" tabindex="0">
            <button
                class="flex items-center justify-center h-10 w-10 rounded-full border-2 border-theme bg-white/10 focus:outline-none focus:ring-2 focus-ring-theme">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4" fill="#28A745" />
                    <rect x="5" y="15" width="14" height="6" rx="3" fill="#3CCFCF" />
                    <circle cx="12" cy="8" r="3.2" fill="#fff" />
                </svg>
            </button>
            <div class="user-dropdown-menu left-0 right-auto mt-2 bg-white/80 backdrop-blur-sm rounded-xl shadow-lg border border-gray-100" style="min-width: 180px;">
                <div class="px-4 py-3 border-b border-gray-100">
                    <div class="font-bold text-gray-800 text-base">{{ Auth::user()->name ?? 'User Name' }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        @php
                            $userRoles = Auth::user()->getRoleNames();
                            $roleName = $userRoles->isNotEmpty() ? $userRoles->first() : 'الدور غير محدد';
                        @endphp
                        {{ $roleName }}
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                    @csrf
                    <button type="submit"
                        class="w-full text-right px-4 py-2 text-red-600 hover:bg-red-50 font-semibold transition rounded-b-xl">تسجيل
                        الخروج</button>
                </form>
            </div>
        </div>
    </div>
</nav>
