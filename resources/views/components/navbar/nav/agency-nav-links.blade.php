@php
    $showPoliciesLink = Auth::user()->hasRole('agency-admin') || Auth::user()->can('policies.view');
    $showPoliciesLinkUser = Auth::check() && Auth::user()->agency_id !== null && !Auth::user()->hasRole('agency-admin');
@endphp
<div class="flex items-center justify-center flex-1 gap-2">
    <!-- الرئيسية -->
    <div class="relative group nav-item flex items-center px-2 py-1 rounded-full {{ request()->routeIs('agency.dashboard') ? 'active' : '' }}">
        <x-icon-button
            icon="fas fa-home"
            class="bg-blue-900"
            tooltip="الرئيسية"
            href="{{ route('agency.dashboard') }}"
            label="الرئيسية"
        />
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
        <x-icon-button
            icon="fas fa-cash-register"
            tooltip="المبيعات"
            label="المبيعات"
            href="#"
            class="!px-2 !py-1"
            :active="request()->routeIs('agency.sales.*') || request()->routeIs('agency.collections') || request()->routeIs('agency.invoices.*')"
            dropdown="true"
        />
        <div class="dropdown-sales absolute right-0 top-full mt-2 min-w-[200px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
            <x-dropdown-link
                :href="route('agency.sales.index')"
                icon="fas fa-plus-circle"
                label="إنشاء طلبات"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('sales.view')"
            />
            <x-dropdown-link
                :href="route('agency.collections')"
                icon="fas fa-hand-holding-usd"
                label="التحصيل"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('collections.view')"
            />
            <x-dropdown-link
                href="#"
                icon="fas fa-file-invoice"
                label="الفواتير"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('invoices.view')"
            />
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
        <x-icon-button
            icon="fas fa-cogs"
            tooltip="تهيئة إعدادات الشركة"
            label="تهيئة إعدادات الشركة"
            href="#"
            class="!px-2 !py-1"
            :active="request()->routeIs('agency.profile') || request()->routeIs('agency.dynamic-lists') || request()->routeIs('agency.approval-sequences') || request()->routeIs('agency.policies')"
            dropdown="true"
        />
        <div class="dropdown-settings absolute right-0 top-full mt-2 min-w-[220px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
            <x-dropdown-link
                :href="route('agency.profile')"
                icon="fas fa-building"
                label="بيانات الشركة"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('profile.view')"
            />
            <x-dropdown-link
                :href="route('agency.dynamic-lists')"
                icon="fas fa-list-alt"
                label="تهيئة القوائم"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('lists.view')"
            />
            <x-dropdown-link
                href="#"
                icon="fas fa-file-invoice-dollar"
                label="تهيئة الفواتير"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('invoice-setup.view')"
            />
            <x-dropdown-link
                :href="route('agency.approval-sequences')"
                icon="fas fa-random"
                label="تسلسل الموافقات"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('sequences.view')"
            />
            <x-dropdown-link
                href="#"
                icon="fas fa-percentage"
                label="تهيئة العمولات"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('commissions-setup.view')"
            />
            <x-dropdown-link
                :href="route('agency.policies')"
                icon="fas fa-file-contract"
                label="سياسات الوكالة"
                :show="Auth::user()->hasRole('agency-admin')"
            />
        </div>
    </div>
    @endif

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
        <x-icon-button
            icon="fas fa-users"
            tooltip="الموارد البشرية"
            label="الموارد البشرية"
            href="#"
            class="!px-2 !py-1"
            :active="request()->routeIs('agency.hr.employees.*') || request()->routeIs('agency.roles') || request()->routeIs('agency.users') || request()->routeIs('agency.permissions')"
            dropdown="true"
        />
        <div class="dropdown-users absolute right-0 top-full mt-2 min-w-[200px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
            <x-dropdown-link
                :href="route('agency.hr.employees.index')"
                icon="fas fa-users"
                label="الموظفين"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('employees.view')"
            />
            <x-dropdown-link
                :href="route('agency.roles')"
                icon="fas fa-user-shield"
                label="الأدوار"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('roles.view')"
            />
            <x-dropdown-link
                :href="route('agency.users')"
                icon="fas fa-user"
                label="المستخدمين"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('users.view')"
            />
            <x-dropdown-link
                :href="route('agency.permissions')"
                icon="fas fa-key"
                label="الصلاحيات"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('permissions.view')"
            />
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
        <x-icon-button
            icon="fas fa-wallet"
            tooltip="الحسابات"
            label="الحسابات"
            href="#"
            class="!px-2 !py-1"
            :active="request()->routeIs('agency.accounts') || request()->routeIs('agency.customers.add') || request()->routeIs('agency.providers')"
            dropdown="true"
        />
        <div class="dropdown-accounts absolute right-0 top-full mt-2 min-w-[200px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
            <x-dropdown-link
                :href="route('agency.accounts')"
                icon="fas fa-file-invoice-dollar"
                label="التقارير المالية"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('accounts.view')"
            />
            <x-dropdown-link
                :href="route('agency.customers.add')"
                icon="fas fa-folder-open"
                label="ملفات العملاء"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('customers.view')"
            />
            <x-dropdown-link
                :href="route('agency.providers')"
                icon="fas fa-briefcase"
                label="ملفات المزودين"
                :show="Auth::user()->hasRole('agency-admin') || Auth::user()->can('providers.view')"
            />
        </div>
    </div>
    @endif

    <!--سياسات الشركة لمستخدمي الوكالة-->
    @if($showPoliciesLinkUser)
        <x-icon-button
            icon="fas fa-file-contract"
            tooltip="سياسات الوكالة"
            label="سياسات الوكالة"
            href="{{ route('agency.policies.view') }}"
            :active="request()->routeIs('agency.policies.view')"
        />
    @endif
</div> 