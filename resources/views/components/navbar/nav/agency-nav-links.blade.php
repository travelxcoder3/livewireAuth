@php
    $showPoliciesLinkUser = Auth::check() && Auth::user()->agency_id !== null && !Auth::user()->hasRole('agency-admin');
@endphp

<div class="flex items-center justify-center flex-1 gap-2 group/nav">
    {{-- الرئيسية --}}
    <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
        <x-navbar.buttons.icon-button icon="fas fa-home" tooltip="الرئيسية" label="الرئيسية"
            href="{{ route('agency.dashboard') }}" :active="request()->routeIs('agency.dashboard')" />
    </div>

        {{-- المبيعات --}}
        @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('sales.view'))
            <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
                <x-navbar.buttons.icon-button icon="fas fa-cash-register" tooltip="المبيعات" label="المبيعات"
                    href="{{ route('agency.sales.index') }}" :active="request()->routeIs('agency.sales.*')" />
            </div>
        @endif

        @if (Auth::check() && Auth::user()->hasRole('agency-admin'))
            <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
                <x-navbar.buttons.icon-button
                    icon="fas fa-bullseye"
                    tooltip="الأهداف الشهرية"
                    label="الأهداف"
                    href="{{ route('agency.monthly-targets') }}"
                    :active="request()->routeIs('agency.monthly-targets')" />
            </div>
        @endif

        {{-- الحسابات --}}
        @php
            $showAccountsDropdown =
                Auth::user()->hasRole('agency-admin') ||
                Auth::user()->can('accounts.view') ||
                Auth::user()->can('invoices.view')||
                Auth::user()->can('providers.view') ||
                Auth::user()->can('customers.view')||
                Auth::user()->can('quotations.view');
        @endphp

        @if ($showAccountsDropdown)
            <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
                <x-navbar.buttons.icon-button icon="fas fa-wallet" tooltip="الحسابات" label="الحسابات" href="#"
                    class="!px-2 !py-1" :active="request()->routeIs('agency.sales-invoices') ||
                        request()->routeIs('agency.providers') ||
                        request()->routeIs('agency.customers.add')" dropdown="true" />
                <div
                    class="dropdown-accounts absolute right-0 top-full min-w-[200px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                

                    <div class="relative group/audit">
                        {{-- العنوان الرئيسي بدون تنقّل --}}
                        <x-navbar.buttons.dropdown-link
                            href="#"
                            x-on:click.prevent
                            icon="fas fa-clipboard-check"
                            label="مراجعة"
                            :show="true"
                            class="pr-8 cursor-default select-none" />

                        <i class="fas fa-angle-left absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>

                        {{-- القائمة الفرعية (تفتح يسار القائمة الرئيسية) --}}
                        <div class="absolute right-full top-0 min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover/audit:block">
                            <x-navbar.buttons.dropdown-link
                            :href="route('agency.sales-review')"     
                                icon="fas fa-chart-line"
                                label="مراجعة المبيعات"
                                :show="true" />

                            <x-navbar.buttons.dropdown-link
                                :href="route('agency.audit.accounts')" 
                                icon="fas fa-wallet"
                                label="مراجعة الحسابات"
                                :show="true" />

                            <x-navbar.buttons.dropdown-link
                                :href="route('agency.invoices.review')"    
                                icon="fas fa-file-invoice"
                                label="مراجعة فواتير المبيعات"
                                :show="true" />
                        </div>
                    </div>



                    <div class="relative group/audit">
                        {{-- العنوان الرئيسي بدون تنقّل --}}
                        <x-navbar.buttons.dropdown-link
                            href="#"
                            x-on:click.prevent
                            icon="fas fa-clipboard-check"
                            label="فواتير"
                            :show="true"
                            class="pr-8 cursor-default select-none" />

                        <i class="fas fa-angle-left absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>

                        {{-- القائمة الفرعية (تفتح يسار القائمة الرئيسية) --}}
                        <div class="absolute right-full top-0 min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover/audit:block">
                        <x-navbar.buttons.dropdown-link
                            :href="route('agency.sales-invoices')"     
                                icon="fas fa-file-invoice-dollar"
                                label="فواتير المبيعات"
                                :show="true" />

                            <x-navbar.buttons.dropdown-link
                                :href="route('agency.customer-detailed-invoices')"     
                                icon="fas fa-file-invoice"
                                label="فواتير العملاء "
                                :show="true" />

                            <x-navbar.buttons.dropdown-link
                                :href="route('agency.provider-detailed-invoices')"
                                icon="fas fa-file-contract"
                                label="فواتير المزودين "
                                :show="true" />

                            <x-navbar.buttons.dropdown-link
                            :href="route('agency.quotation')" 
                                icon="fas fa-file-signature"
                                label="عرض السعر  "
                                :show="true" />
                        </div>
                    </div>


                    <div class="relative group/audit">
                        {{-- العنوان الرئيسي بدون تنقّل --}}
                        <x-navbar.buttons.dropdown-link
                            href="#"
                            x-on:click.prevent
                            icon="fas fa-clipboard-check"
                            label="اداره حسابات الشركة"
                            :show="true"
                            class="pr-8 cursor-default select-none" />

                        <i class="fas fa-angle-left absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>

                        {{-- القائمة الفرعية (تفتح يسار القائمة الرئيسية) --}}
                        <div class="absolute right-full top-0 min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover/audit:block">
                            <x-navbar.buttons.dropdown-link
                                :href="route('agency.customers.add')"     
                                icon="fas fa-chart-line"
                                label=" ملفات العملاء "
                                :show="true" />

                            <x-navbar.buttons.dropdown-link
                                :href="route('agency.providers')"
                                icon="fas fa-wallet"
                                label="ملفات المزودين   "
                                :show="true" />

                            <x-navbar.buttons.dropdown-link
                                :href="route('agency.hr.employee-files')"
                                icon="fas fa-file-invoice"
                                label="ملفات الموظفين    "
                                :show="true" />
                        </div>
                    </div>


                    <div class="relative group/audit">
                        {{-- العنوان الرئيسي بدون تنقّل --}}
                        <x-navbar.buttons.dropdown-link
                            href="#"
                            x-on:click.prevent
                            icon="fas fa-clipboard-check"
                            label="  كشف حساب "
                            :show="true"
                            class="pr-8 cursor-default select-none" />

                        <i class="fas fa-angle-left absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>

                        {{-- القائمة الفرعية (تفتح يسار القائمة الرئيسية) --}}
                        <div class="absolute right-full top-0 min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover/audit:block">

                                <x-navbar.buttons.dropdown-link
                                :href="route('agency.statements.customers')"
                                icon="fas fa-chart-line"
                                label=" كشف حساب العملاء "
                                :show="true" />

                            <x-navbar.buttons.dropdown-link
                                :href="route('agency.reports.provider-ledger')"
                                icon="fas fa-wallet"
                                label="كشف حساب المزودين   "
                                :show="true" />

                        </div>
                    </div>

                
            
            </div>
            </div>
        @endif

       @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('collection.view')|| Auth::user()->can('collection.employee.view'))
            <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
                <x-navbar.buttons.icon-button
                    icon="fas fa-hand-holding-usd" tooltip="التحصيل" label="التحصيل"
                    href="#"
                    class="!px-2 !py-1"
                    dropdown="true"
                    :active="request()->routeIs('agency.employee-collections') || request()->routeIs('agency.employee-collections.all')"
                />
                <div class="dropdown-settings absolute right-0 top-full min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('collection.employee.view'))
                        <x-navbar.buttons.dropdown-link
                            :href="route('agency.employee-collections')"
                            icon="fas fa-user-friends"
                            label="تحصيلات الموظفين"
                            :show="true" />
                    @endif
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('collection.view'))
                        <x-navbar.buttons.dropdown-link
                            :href="route('agency.employee-collections.all')"
                            icon="fas fa-clipboard-list"
                            label="عرض التحصيلات"
                            :show="true" />
                    @endif
                </div>
            </div>
        @endif

        {{-- الموارد البشرية --}}
        @php
            $showHRDropdown =
                Auth::user()->hasRole('agency-admin') ||
                Auth::user()->can('employees.view') ||
                Auth::user()->can('roles.view') ||
                Auth::user()->can('users.view') ||
                Auth::user()->can('permissions.view');
        @endphp
        @if ($showHRDropdown)
            <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
                <x-navbar.buttons.icon-button icon="fas fa-users" tooltip="الموارد البشرية" label="الموارد البشرية"
                    href="#" class="!px-2 !py-1" :active="request()->routeIs('agency.hr.employees.*') ||
                        request()->routeIs('agency.roles') ||
                        request()->routeIs('agency.users') ||
                        request()->routeIs('agency.permissions')" dropdown="true" />
                <div
                    class="dropdown-users absolute right-0 top-full min-w-[200px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('employees.view'))
                        <x-navbar.buttons.dropdown-link :href="route('agency.hr.employees.index')" icon="fas fa-users" label="الموظفين"
                            :show="true" />
                    @endif
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('roles.view'))
                        <x-navbar.buttons.dropdown-link :href="route('agency.roles')" icon="fas fa-user-shield" label="الأدوار"
                            :show="true" />
                    @endif
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('users.view'))
                        <x-navbar.buttons.dropdown-link :href="route('agency.users')" icon="fas fa-user" label="المستخدمين"
                            :show="true" />
                    @endif
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('permissions.view'))
                        <x-navbar.buttons.dropdown-link :href="route('agency.permissions')" icon="fas fa-key" label="الصلاحيات"
                            :show="true" />
                    @endif
                </div>
            </div>
        @endif
        {{-- التقارير --}}
        @php
            $showReportsDropdown =
                Auth::user()->hasRole('agency-admin') ||
                Auth::user()->can('reportsSales.view') ||
                Auth::user()->can('reportsAccounts.view') ||
                Auth::user()->can('reportCustomers.view') ||
                Auth::user()->can('reportCustomerAccounts.view')||
                Auth::user()->can('reportEmployeeSales.view')|| 
                Auth::user()->can('reportProvider.view');
        @endphp

        @if ($showReportsDropdown)
            <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
                <x-navbar.buttons.icon-button icon="fas fa-chart-line" tooltip="التقارير" label="التقارير" href="#"
                    class="!px-2 !py-1"
                    :active="request()->routeIs('agency.reports.sales') || request()->routeIs('agency.reports.accounts')"
                    dropdown="true" />

                <div
                    class="dropdown-reports absolute right-0 top-full min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">

                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('reportsSales.view'))
                        <x-navbar.buttons.dropdown-link
                            :href="route('agency.reports.sales')"
                            icon="fas fa-chart-line"
                            label="تقرير المبيعات"
                            :show="true" />
                    @endif

            

                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('reportCustomers.view'))
                    <div class="relative group/clients">
                        {{-- العنوان الرئيسي بدون تنقّل --}}
                        <x-navbar.buttons.dropdown-link
                            href="#"
                            x-on:click.prevent
                            icon="fas fa-user-check"
                            label="تقرير العملاء"
                            :show="true"
                            class="pr-8 cursor-default select-none" />

                        <i class="fas fa-angle-left absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>

                        {{-- القائمة الفرعية --}}
                        <div class="absolute right-full top-0 min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover/clients:block">
                        <x-navbar.buttons.dropdown-link
                            :href="route('agency.reports.customers-follow-up')"
                            icon="fas fa-user-clock"
                            label="تتبع العملاء"
                            :show="true" />

                        <x-navbar.buttons.dropdown-link
                            :href="route('agency.reports.customer-sales')"  
                            icon="fas fa-list-check"
                            label="عمليات العملاء"
                            :show="true" />
                        </div>
                        </div>
                            @endif

                            @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('reportEmployeeSales.view'))
                                <x-navbar.buttons.dropdown-link
                                    :href="route('agency.reports.provider-sales')"
                                    icon="fas fa-user-tie"
                                    label=" تقارير المزودين "
                                    :show="true" />
                            @endif

                            @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('reportEmployeeSales.view'))
                                <x-navbar.buttons.dropdown-link
                                    :href="route('agency.reports.employee-sales')" 
                                    icon="fas fa-user-tie"
                                    label=" تقارير الموظفين "
                                    :show="true" />
                            @endif


                            @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('reportQuotation.view'))
                                <x-navbar.buttons.dropdown-link
                                    :href="route('agency.reports.quotations')"
                                    icon="fas fa-file-invoice"
                                    label="تقارير عروض الاسعار"
                                    :show="true" />
                            @endif

                            </div>
                        </div>
        @endif



        @php
    use Illuminate\Support\Facades\DB;
    use App\Models\ApprovalRequest;

    // جميع تسلسلات المستخدم في وكالته
    $mySequenceIds = DB::table('approval_sequence_users as asu')
        ->join('approval_sequences as s','s.id','=','asu.approval_sequence_id')
        ->where('asu.user_id', Auth::id())
        ->where('s.agency_id', Auth::user()->agency_id)
        ->pluck('asu.approval_sequence_id');

    // عدد الطلبات المعلّقة التي تنتظر موافقته
    $pendingApprovalsForMe = ApprovalRequest::where('status', 'pending')
        ->whereIn('approval_sequence_id', $mySequenceIds)
        ->count();
@endphp

@can('approvals.access')
    <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
        <x-navbar.buttons.icon-button
            icon="fas fa-clipboard-check"
            tooltip="طلبات الموافقة"
            label="طلبات الموافقة"
            href="{{ route('agency.approvals.index') }}"
            :active="request()->routeIs('agency.approvals.index')"
            :has-notification="$pendingApprovalsForMe > 0"
            :notification-count="$pendingApprovalsForMe"
        />
    </div>
@endcan


        {{-- إعدادات الشركة --}}
        @php
            $showSettingsDropdown =
                Auth::user()->hasRole('agency-admin') ||
                Auth::user()->can('agency.profile.view') ||
                Auth::user()->can('lists.view') ||
                Auth::user()->can('sequences.view') ||
                Auth::user()->can('commissions-setup.view')||
                Auth::user()->can('commissions-setup.view');
      
        @endphp


        @if ($showSettingsDropdown)
            <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
                <x-navbar.buttons.icon-button    icon="fas fa-cogs" tooltip="إعدادات الشركة" label="إعدادات الشركة"
                    href="#" class="!px-2 !py-1" :active="request()->routeIs('agency.profile') ||
                        request()->routeIs('agency.dynamic-lists') ||
                        request()->routeIs('agency.approval-sequences') ||
                        request()->routeIs('agency.policies')" dropdown="true" />
                <div
                    class="dropdown-settings absolute right-0 top-full min-w-[220px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('agency.profile.view'))
                        <x-navbar.buttons.dropdown-link :href="route('agency.profile')"  icon="fas fa-building" label="بيانات الشركة"
                            :show="true" />
                    @endif
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('lists.view'))
                        <x-navbar.buttons.dropdown-link :href="route('agency.dynamic-lists')" icon="fas fa-list-ul" label="تهيئة القوائم"
                            :show="true" />
                    @endif
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('sequences.view'))
                        <x-navbar.buttons.dropdown-link :href="route('agency.approval-sequences')"  icon="fas fa-project-diagram" label="تسلسل الموافقات"
                            :show="true" />
                    @endif
                                @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('commissions-setup.view'))
                        <x-navbar.buttons.dropdown-link href="{{ route('agency.commissions') }}"  icon="fas fa-percent"
                            label="تهيئة العمولات" :show="true" />
                    @endif
                    {{-- داخل dropdown-settings --}}
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('commission-policies.view'))
                        <x-navbar.buttons.dropdown-link
                            :href="route('agency.commission-policies')"
                            icon="fas fa-user-cog"
                            label="تهيئة عمولات الموظفين"
                            :show="true" />
                    @endif
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('policies.view'))
                        <x-navbar.buttons.dropdown-link :href="route('agency.policies')"   icon="fas fa-file-contract"

                            label="سياسات الوكالة" :show="true" />
                    @endif
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('obligations.view'))
                        <x-navbar.buttons.dropdown-link : href="{{ route('agency.obligations') }}"
                            icon="fas fa-balance-scale" label="التزامات و قيود " :show="true" />
                    @endif
                    @if (Auth::user()->hasRole('agency-admin') || Auth::user()->can('backup.view'))
                        <x-navbar.buttons.dropdown-link : href="{{ route('agency.backups.index') }}"
                            icon="fas fa-database" label="نسح واستعاده" :show="true" />
                    @endif

             

                    </div>
                </div>
        @endif



        {{-- سياسات المستخدم العادي --}}
        @if ($showPoliciesLinkUser)
            <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10">
                <x-navbar.buttons.icon-button icon="fas fa-file-contract" tooltip="سياسات الوكالة" label="سياسات الوكالة"
                    href="{{ route('agency.policies.view') }}" :active="request()->routeIs('agency.policies.view')" />
            </div>
        @endif

        <div class="relative nav-item flex items-center px-2 py-1 rounded-full group-hover/nav:bg-white/10 group">
            <a href="{{ route('agency.obligations-view') }}"
                class="flex items-center px-2 py-1 rounded-full bg-white/10 hover:bg-white/20 transition group">
                <span class="nav-icon icon-button">
                    <i class="fas fa-balance-scale text-white text-base"></i>
                </span>
                <span
                    class="nav-text text-xs text-white whitespace-nowrap ml-2 transition-all duration-300 ease-in-out opacity-0 max-w-0 group-hover:opacity-100 group-hover:max-w-xs">
                    التزامات وقيود
                </span>
            </a>
        </div>


</div>