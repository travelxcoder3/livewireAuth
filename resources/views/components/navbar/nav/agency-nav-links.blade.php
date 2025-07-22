@php
    $showPoliciesLinkUser = Auth::check() && Auth::user()->agency_id !== null && !Auth::user()->hasRole('agency-admin');
@endphp

<div class="flex items-center justify-center flex-1 gap-2 group/nav">
    {{-- الرئيسية --}}
    <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
        <x-navbar.buttons.icon-button
            icon="fas fa-home"
            tooltip="الرئيسية"
            label="الرئيسية"
            href="{{ route('agency.dashboard') }}"
            :active="request()->routeIs('agency.dashboard')"
        />
    </div>

    {{-- المبيعات --}}
    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('sales.view'))
        <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
            <x-navbar.buttons.icon-button
                icon="fas fa-cash-register"
                tooltip="المبيعات"
                label="المبيعات"
                href="{{ route('agency.sales.index') }}"
                :active="request()->routeIs('agency.sales.*')"
            />
        </div>
    @endif

    {{-- الحسابات --}}
    @php
        $showAccountsDropdown = Auth::user()->hasRole('agency-admin') || Auth::user()->can('accounts.view') || Auth::user()->can('providers.view') || Auth::user()->can('customers.view');
    @endphp
    @if($showAccountsDropdown)
        <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
            <x-navbar.buttons.icon-button
                icon="fas fa-wallet"
                tooltip="الحسابات"
                label="الحسابات"
                href="#"
                class="!px-2 !py-1"
                :active="request()->routeIs('agency.accounts') || request()->routeIs('agency.providers') || request()->routeIs('agency.customers.add')"
                dropdown="true"
            />
            <div class="dropdown-accounts absolute right-0 top-full min-w-[200px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                <x-navbar.buttons.dropdown-link :href="route('agency.accounts')" icon="fas fa-file-invoice-dollar" label="مراجعة الحسابات" :show="true" />
                <x-navbar.buttons.dropdown-link href="#" icon="fas fa-file-invoice" label="الفواتير" :show="true" />
                <x-navbar.buttons.dropdown-link :href="route('agency.customers.add')" icon="fas fa-folder-open" label="ملفات العملاء" :show="true" />
                <x-navbar.buttons.dropdown-link :href="route('agency.providers')" icon="fas fa-briefcase" label="ملفات المزودين" :show="true" />
            </div>
        </div>
    @endif

    {{-- التحصيل --}}
    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('collections.view'))
        <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
            <x-navbar.buttons.icon-button
                icon="fas fa-hand-holding-usd"
                tooltip="التحصيل"
                label="التحصيل"
                href="{{ route('agency.collections') }}"
                :active="request()->routeIs('agency.collections')"
            />
        </div>
    @endif

    {{-- الموارد البشرية --}}
    @php
        $showHRDropdown = Auth::user()->hasRole('agency-admin') || Auth::user()->can('employees.view') || Auth::user()->can('roles.view') || Auth::user()->can('users.view') || Auth::user()->can('permissions.view');
    @endphp
    @if($showHRDropdown)
        <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
            <x-navbar.buttons.icon-button
                icon="fas fa-users"
                tooltip="الموارد البشرية"
                label="الموارد البشرية"
                href="#"
                class="!px-2 !py-1"
                :active="request()->routeIs('agency.hr.employees.*') || request()->routeIs('agency.roles') || request()->routeIs('agency.users') || request()->routeIs('agency.permissions')"
                dropdown="true"
            />
            <div class="dropdown-users absolute right-0 top-full min-w-[200px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                <x-navbar.buttons.dropdown-link :href="route('agency.hr.employees.index')" icon="fas fa-users" label="الموظفين" :show="true" />
                <x-navbar.buttons.dropdown-link :href="route('agency.roles')" icon="fas fa-user-shield" label="الأدوار" :show="true" />
                <x-navbar.buttons.dropdown-link :href="route('agency.users')" icon="fas fa-user" label="المستخدمين" :show="true" />
                <x-navbar.buttons.dropdown-link :href="route('agency.permissions')" icon="fas fa-key" label="الصلاحيات" :show="true" />
            </div>
        </div>
    @endif

    {{-- التقارير --}}
    @php
        $showReportsDropdown =
            Auth::user()->hasRole('agency-admin') ||
            Auth::user()->can('sales.view') ||
            Auth::user()->can('accounts.view');
    @endphp

    @if ($showReportsDropdown)
        <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
            <x-navbar.buttons.icon-button icon="fas fa-chart-line" tooltip="التقارير" label="التقارير" href="#"
                class="!px-2 !py-1" :active="request()->routeIs('agency.reports.sales') || request()->routeIs('agency.reports.accounts')" dropdown="true" />

            <div
                class="dropdown-reports absolute right-0 top-full min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                <x-navbar.buttons.dropdown-link :href="route('agency.reports.sales')" icon="fas fa-chart-bar" label="تقرير المبيعات"
                    :show="true" />
                <x-navbar.buttons.dropdown-link :href="route('agency.reports.accounts')" icon="fas fa-file-invoice-dollar"
                    label="تقرير الحسابات" :show="true" />
            </div>
        </div>
    @endif

    {{-- طلبات الموافقة --}}
    <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
        <x-navbar.buttons.icon-button
            icon="fas fa-clipboard-check"
            tooltip="طلبات الموافقة"
            label="طلبات الموافقة"
            href="#"
            :active="false"
        />
    </div>

    {{-- إعدادات الشركة --}}
    @php
        $showSettingsDropdown = Auth::user()->hasRole('agency-admin') || Auth::user()->can('profile.view') || Auth::user()->can('lists.view') || Auth::user()->can('invoice-setup.view') || Auth::user()->can('sequences.view') || Auth::user()->can('commissions-setup.view');
    @endphp
    @if($showSettingsDropdown)
        <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
            <x-navbar.buttons.icon-button
                icon="fas fa-cogs"
                tooltip="إعدادات الشركة"
                label="إعدادات الشركة"
                href="#"
                class="!px-2 !py-1"
                :active="request()->routeIs('agency.profile') || request()->routeIs('agency.dynamic-lists') || request()->routeIs('agency.approval-sequences') || request()->routeIs('agency.policies')"
                dropdown="true"
            />
            <div class="dropdown-settings absolute right-0 top-full min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                <x-navbar.buttons.dropdown-link :href="route('agency.profile')" icon="fas fa-building" label="بيانات الشركة" :show="true" />
                <x-navbar.buttons.dropdown-link :href="route('agency.dynamic-lists')" icon="fas fa-list-alt" label="تهيئة القوائم" :show="true" />
                <x-navbar.buttons.dropdown-link href="#" icon="fas fa-file-invoice-dollar" label="تهيئة الفواتير" :show="true" />
                <x-navbar.buttons.dropdown-link :href="route('agency.approval-sequences')" icon="fas fa-random" label="تسلسل الموافقات" :show="true" />
                <x-navbar.buttons.dropdown-link href="{{ route('agency.commissions') }}" icon="fas fa-percentage" label="تهيئة العمولات" :show="true" />
                <x-navbar.buttons.dropdown-link :href="route('agency.policies')" icon="fas fa-file-contract" label="سياسات الوكالة" :show="true" />
                <x-navbar.buttons.dropdown-link :   href="{{ route('agency.obligations') }}" icon="fas fa-file-contract" label="التزامات و قيود " :show="true" />
            </div>
        </div>
    @endif

    {{-- سياسات المستخدم العادي --}}
    @if($showPoliciesLinkUser)
        <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
            <x-navbar.buttons.icon-button
                icon="fas fa-file-contract"
                tooltip="سياسات الوكالة"
                label="سياسات الوكالة"
                href="{{ route('agency.policies.view') }}"
                :active="request()->routeIs('agency.policies.view')"
            />
        </div>
    @endif

    <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
        <a href="{{ route('agency.obligations-view') }}" class="flex items-center px-2 py-1 rounded-full bg-white/10 hover:bg-white/20 transition group">
            <span class="nav-icon icon-button">
                <i class="fas fa-file-contract text-white text-base"></i>
            </span>
            <span class="nav-text text-xs text-white whitespace-nowrap ml-2 transition-all duration-300 ease-in-out opacity-0 max-w-0 group-hover:opacity-100 group-hover:max-w-xs">
                التزامات وقيود
            </span>
        </a>
    </div>


</div>
