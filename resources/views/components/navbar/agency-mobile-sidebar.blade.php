@php
    use App\Models\ApprovalRequest;
    use Illuminate\Support\Facades\DB;
    use App\Services\ThemeService;

    $user = Auth::user();
    $agencyName = $user && $user->agency ? $user->agency->name : 'Travel-X';

    // إشعارات طلبات الموافقة حسب تسلسلات المستخدم
    $mySequenceIds = DB::table('approval_sequence_users as asu')
        ->join('approval_sequences as s', 's.id', '=', 'asu.approval_sequence_id')
        ->where('asu.user_id', Auth::id())
        ->where('s.agency_id', $user->agency_id)
        ->pluck('asu.approval_sequence_id');

    $pendingApprovalsForMe = ApprovalRequest::where('status', 'pending')
        ->whereIn('approval_sequence_id', $mySequenceIds)
        ->count();

    // رابط سياسات المستخدم غير الأدمن
    $showPoliciesLinkUser = Auth::check() && $user->agency_id !== null && !$user->hasRole('agency-admin');

    // التحقق من الصلاحيات للمناطق المختلفة
    $showAccountsDropdown = $user->hasRole('agency-admin') ||
        $user->can('sales-review.view') ||
        $user->can('accounts-review.view') ||
        $user->can('invoices-review.view') ||
        $user->can('sales-invoices.view') ||
        $user->can('customer-invoices.view') ||
        $user->can('provider-invoices.view') ||
        $user->can('providers.view') ||
        $user->can('customers.view')||
        $user->can('employees-manage.view') ||
        $user->can('customers-statement.view') ||
        $user->can('providers-statement.view') ||
        $user->can('employee-statement.view') ||
        $user->can('quotations.view');

    $showHRDropdown = $user->hasRole('agency-admin') ||
        $user->can('employees.view') ||
        $user->can('roles.view') ||
        $user->can('users.view') ||
        $user->can('permissions.view');

    $showReportsDropdown = $user->hasRole('agency-admin') ||
        $user->can('reportsSales.view') ||
        $user->can('reportCustomerAccounts.view')||
        $user->can('reportEmployeeSales.view')|| 
        $user->can('reportQuotation.view')||
        $user->can('reportCustomersSales.view')||
        $user->can('reportProvider.view');

    $showSettingsDropdown = $user->hasRole('agency-admin') ||
        $user->can('agency.profile.view') ||
        $user->can('lists.view') ||
        $user->can('sequences.view') ||
        $user->can('policies.view') ||
        $user->can('obligations.view') ||
        $user->can('backup.view');

    $themes = ThemeService::getThemeColors();
@endphp


<div class="fixed inset-0 z-50 flex lg:hidden">
    <!-- خلفية شفافة -->
    <div class="fixed inset-0 bg-black bg-opacity-40" @click="mobileSidebarOpen = false"></div>

    <!-- القائمة الجانبية -->
    <aside class="relative w-64 sm:w-72 max-w-full bg-white shadow-xl h-full flex flex-col rtl:border-l rtl:border-gray-200 ltr:border-r ltr:border-gray-200 overflow-y-auto max-h-screen">
        
        <!-- رأس القائمة -->
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
               <a href="{{ route('agency.notifications.index') }}" class="text-black hover:text-blue-500">
    <x-navbar.buttons.icon-button
        icon="fas fa-bell"
        label="الإشعارات"
        tooltip="الإشعارات"
        :has-notification="false"
        iconColor="text-black" 
    />
</a>
                <img src="{{ asset('images/logo-travelx.png') }}" alt="TRAVEL-X Logo" class="h-10 w-auto object-contain">
                <!-- زر الإشعارات خارج القائمة الجانبية -->
        
            </div>
            <button @click="mobileSidebarOpen = false" class="text-gray-500 hover:text-red-500 focus:outline-none">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <!-- روابط -->
        <nav class="flex-1 flex flex-col gap-1 px-4 py-4">
            <!-- الرئيسية -->
            <a href="{{ route('agency.dashboard') }}" @click="mobileSidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fas fa-home"></i>
                <span>الرئيسية</span>
            </a>

            <!-- المبيعات -->
            @if($user->hasRole('agency-admin') || $user->can('sales.view'))
                <a href="{{ route('agency.sales.index') }}" @click="mobileSidebarOpen = false"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                    <i class="fas fa-cash-register"></i>
                    <span>المبيعات</span>
                </a>
            @endif

            <!-- الأهداف الشهرية -->
            @if($user->hasRole('agency-admin') || $user->can('month-goals.view'))
                <a href="{{ route('agency.monthly-targets') }}" @click="mobileSidebarOpen = false"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                    <i class="fas fa-bullseye"></i>
                    <span>الأهداف الشهرية</span>
                </a>
            @endif

            <!-- الحسابات -->
            @if($showAccountsDropdown)
                <div x-data="{open:false}" class="relative">
                    <button @click="open = !open"
                            class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-wallet"></i>
                            <span>الحسابات</span>
                        </div>
                        <i class="fas" :class="{'fa-chevron-down': !open, 'fa-chevron-up': open}"></i>
                    </button>

                    <div x-show="open" class="rtl:pr-4 ltr:pl-4 space-y-1">
                        {{-- مراجعة --}}
                        @if($user->hasRole('agency-admin') ||  $user->can('sales-review.view') || $user->can('accounts-review.view') || $user->can('invoices-review.view'))
                            <div class="px-3 pt-2 text-xs text-gray-500">مراجعة</div>
                            @if ($user->hasRole('agency-admin') || $user->can('sales-review.view'))
                                <a href="{{ route('agency.sales-review') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-chart-line"></i><span>مراجعة المبيعات</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('accounts-review.view'))
                                <a href="{{ route('agency.audit.accounts') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-wallet"></i><span>مراجعة الحسابات</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('invoices-review.view'))
                                <a href="{{ route('agency.invoices.review') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-file-invoice"></i><span>مراجعة الفواتير</span>
                                </a>
                            @endif
                        @endif

                        {{-- فواتير --}}
                        @if($user->hasRole('agency-admin') ||  $user->can('sales-invoices.view') || $user->can('customer-invoices.view') || $user->can('provider-invoices.view') || $user->can('quotations.view'))
                            <div class="px-3 pt-3 text-xs text-gray-500">فواتير</div>
                            @if ($user->hasRole('agency-admin') || $user->can('sales-invoices.view'))
                                <a href="{{ route('agency.sales-invoices') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-file-invoice-dollar"></i><span>فواتير المبيعات</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('customer-invoices.view'))
                                <a href="{{ route('agency.customer-detailed-invoices') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-file-invoice"></i><span>فواتير العملاء</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('provider-invoices.view'))
                                <a href="{{ route('agency.provider-detailed-invoices') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-file-contract"></i><span>فواتير المزودين</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('quotations.view'))
                                <a href="{{ route('agency.quotation') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-file-signature"></i><span>عرض السعر</span>
                                </a>
                            @endif
                        @endif

                        {{-- إدارة حسابات الشركة --}}
                        @if ($user->hasRole('agency-admin') ||  $user->can('customers.view') || $user->can('providers.view') || $user->can('employees-manage.view'))
                            <div class="px-3 pt-3 text-xs text-gray-500">إدارة حسابات الشركة</div>
                            @if ($user->hasRole('agency-admin') || $user->can('customers.view'))
                                <a href="{{ route('agency.customers.add') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-folder-open"></i><span>ملفات العملاء</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('providers.view'))
                                <a href="{{ route('agency.providers') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-briefcase"></i><span>ملفات المزودين</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('employees-manage.view'))
                                <a href="{{ route('agency.hr.employee-files') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-id-card"></i><span>ملفات الموظفين</span>
                                </a>
                            @endif
                        @endif

                        {{-- كشف حساب --}}
                        @if ($user->hasRole('agency-admin')  || $user->can('customers-statement.view') || $user->can('providers-statement.view') || $user->can('employee-statement.view'))
                            <div class="px-3 pt-3 text-xs text-gray-500">كشف حساب</div>
                            @if ($user->hasRole('agency-admin') || $user->can('customers-statement.view'))
                                <a href="{{ route('agency.statements.customers') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user-check"></i><span>كشف حساب العميل</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('providers-statement.view'))
                                <a href="{{ route('agency.statements.providers') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-file-contract"></i><span>كشف حساب المزودين</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('employee-statement.view'))
                                <a href="{{ route('agency.statements.employees') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user-tie"></i><span>كشف حساب الموظفين</span>
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            <!-- التحصيل -->
            @if ($user->hasRole('agency-admin') || $user->can('collection.view') || $user->can('collection.employee.view'))
                <div x-data="{open:false}" class="relative">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-hand-holding-usd"></i><span>التحصيل</span>
                        </div>
                        <i class="fas" :class="{'fa-chevron-down': !open, 'fa-chevron-up': open}"></i>
                    </button>
                    <div x-show="open" class="rtl:pr-4 ltr:pl-4">
                        @if ($user->hasRole('agency-admin') || $user->can('collection.employee.view'))
                            <a href="{{ route('agency.employee-collections') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-user-friends"></i><span>تحصيلات الموظفين</span>
                            </a>
                        @endif
                        @if ($user->hasRole('agency-admin') || $user->can('collection.view'))
                            <a href="{{ route('agency.employee-collections.all') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-clipboard-list"></i><span>عرض التحصيلات</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- الموارد البشرية -->
            @if ($showHRDropdown)
                <div x-data="{open:false}" class="relative">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-users"></i><span>الموارد البشرية</span>
                        </div>
                        <i class="fas" :class="{'fa-chevron-down': !open, 'fa-chevron-up': open}"></i>
                    </button>
                    <div x-show="open" class="rtl:pr-4 ltr:pl-4">
                        @if ($user->hasRole('agency-admin') || $user->can('employees.view'))
                            <a href="{{ route('agency.hr.employees.index') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-users"></i><span>الموظفين</span>
                            </a>
                        @endif
                        @if ($user->hasRole('agency-admin') || $user->can('roles.view'))
                            <a href="{{ route('agency.roles') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-user-shield"></i><span>الأدوار</span>
                            </a>
                        @endif
                        @if ($user->hasRole('agency-admin') || $user->can('users.view'))
                            <a href="{{ route('agency.users') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-user"></i><span>المستخدمين</span>
                            </a>
                        @endif
                        @if ($user->hasRole('agency-admin') || $user->can('permissions.view'))
                            <a href="{{ route('agency.permissions') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-key"></i><span>الصلاحيات</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- التقارير -->
            @if ($showReportsDropdown)
                <div x-data="{open:false}" class="relative">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-chart-line"></i><span>التقارير</span>
                        </div>
                        <i class="fas" :class="{'fa-chevron-down': !open, 'fa-chevron-up': open}"></i>
                    </button>
                    <div x-show="open" class="rtl:pr-4 ltr:pl-4">
                        @if ($user->hasRole('agency-admin') || $user->can('reportsSales.view'))
                            <a href="{{ route('agency.reports.sales') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-chart-line"></i><span>تقرير المبيعات</span>
                            </a>
                        @endif

                        {{-- تقرير العملاء مع روابطه الفرعية --}}
                        @if ($user->hasRole('agency-admin') ||  $user->can('reportCustomerAccounts.view') || $user->can('reportCustomersSales.view'))
                            <div class="px-3 pt-3 text-xs text-gray-500">تقرير العملاء</div>
                            @if ($user->hasRole('agency-admin') || $user->can('reportCustomerAccounts.view'))
                                <a href="{{ route('agency.reports.customers-follow-up') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user-clock"></i><span>تتبع العملاء</span>
                                </a>
                            @endif
                            @if ($user->hasRole('agency-admin') || $user->can('reportCustomersSales.view'))
                                <a href="{{ route('agency.reports.customer-sales') }}" @click="mobileSidebarOpen = false"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-list-check"></i><span>عمليات العملاء</span>
                                </a>
                            @endif
                        @endif

                        @if ($user->hasRole('agency-admin') || $user->can('reportProvider.view'))
                            <a href="{{ route('agency.reports.provider-sales') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-user-tie"></i><span>تقارير المزودين</span>
                            </a>
                        @endif

                        @if ($user->hasRole('agency-admin') || $user->can('reportEmployeeSales.view'))
                            <a href="{{ route('agency.reports.employee-sales') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-user-tie"></i><span>تقارير الموظفين</span>
                            </a>
                        @endif

                        @if ($user->hasRole('agency-admin') || $user->can('reportQuotation.view'))
                            <a href="{{ route('agency.reports.quotations') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-file-invoice"></i><span>تقارير عروض الاسعار</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- طلبات الموافقة -->
            @can('approvals.access')
                <a href="{{ route('agency.approvals.index') }}" @click="mobileSidebarOpen = false"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                    <i class="fas fa-clipboard-check"></i>
                    <span>طلبات الموافقة</span>
                    @if($pendingApprovalsForMe > 0)
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                            {{ $pendingApprovalsForMe }}
                        </span>
                    @endif
                </a>
            @endcan

            <!-- إعدادات الشركة -->
            @if ($showSettingsDropdown)
                <div x-data="{open:false}" class="relative">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-cogs"></i><span>إعدادات الشركة</span>
                        </div>
                        <i class="fas" :class="{'fa-chevron-down': !open, 'fa-chevron-up': open}"></i>
                    </button>
                    <div x-show="open" class="rtl:pr-4 ltr:pl-4">
                        @if ($user->hasRole('agency-admin') || $user->can('agency.profile.view'))
                            <a href="{{ route('agency.profile') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-building"></i><span>بيانات الشركة</span>
                            </a>
                        @endif
                        @if ($user->hasRole('agency-admin') || $user->can('lists.view'))
                            <a href="{{ route('agency.dynamic-lists') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-list-ul"></i><span>تهيئة القوائم</span>
                            </a>
                        @endif
                        @if ($user->hasRole('agency-admin') || $user->can('sequences.view'))
                            <a href="{{ route('agency.approval-sequences') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-project-diagram"></i><span>تسلسل الموافقات</span>
                            </a>
                        @endif
                        @if ($user->hasRole('agency-admin') || $user->can('policies.view'))
                            <a href="{{ route('agency.policies') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-file-contract"></i><span>سياسات الوكالة</span>
                            </a>
                        @endif
                        @if ($user->hasRole('agency-admin') || $user->can('obligations.view'))
                            <a href="{{ route('agency.obligations') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-balance-scale"></i><span>التزامات و قيود</span>
                            </a>
                        @endif
                        @if ($user->hasRole('agency-admin') || $user->can('backup.view'))
                            <a href="{{ route('agency.backups.index') }}" @click="mobileSidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-database"></i><span>نسخ واستعاده</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- سياسات المستخدم العادي --}}
            @if ($showPoliciesLinkUser)
                <a href="{{ route('agency.policies.view') }}" @click="mobileSidebarOpen = false"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                    <i class="fas fa-file-contract"></i>
                    <span>سياسات الوكالة</span>
                </a>
            @endif

            <!-- التزامات وقيود (عرض فقط) -->
            <a href="{{ route('agency.obligations-view') }}" @click="mobileSidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fas fa-balance-scale"></i>
                <span>التزامات وقيود</span>
            </a>

            <div class="border-t my-2"></div>

            <!-- إعدادات الثيم -->
            <div class="mb-2">
                <div class="flex items-center gap-3 px-3 py-2">
                    <i class="fas fa-palette"></i>
                    <span>تغيير الثيم</span>
                </div>
                <div class="grid grid-cols-5 gap-2 px-3 py-2">
                    @foreach ($themes as $name => $theme)
                        <button onclick="updateTheme('{{ $name }}')" class="h-7 w-7 rounded-full border"
                                style="background-color: rgb({{ $theme['primary-500'] }})"></button>
                    @endforeach
                </div>
                <div class="flex items-center gap-2 px-3 py-2">
                    <input type="color" id="customHexColorMobile" class="h-7 w-7 rounded-full border cursor-pointer"
                           onchange="handleHexColorChangeMobile(this.value)" />
                    <input type="text" id="customHexInputMobile" maxlength="7" placeholder="#1abc9c"
                           class="border rounded px-2 py-1 text-xs w-20"
                           onchange="handleHexColorChangeMobile(this.value)" />
                    <button onclick="submitHexColorMobile()"
                            class="px-2 py-1 rounded text-white text-xs transition duration-200 shadow"
                            style="background: rgb(var(--primary-500));">
                        تطبيق
                    </button>
                </div>
            </div>

            <!-- إعدادات اللغة -->
            <div class="mb-2">
                <div class="flex items-center gap-3 px-3 py-2">
                    <i class="fas fa-globe"></i>
                    <span>تغيير اللغة</span>
                </div>
                <div class="flex gap-2 px-3 py-2">
                    <a href="/?lang=ar" class="px-3 py-1 rounded bg-gray-100 text-gray-800 text-xs">العربية</a>
                    <a href="/?lang=en" class="px-3 py-1 rounded bg-gray-100 text-gray-800 text-xs">English</a>
                </div>
            </div>

            <div class="border-t my-2"></div>

            <!-- تسجيل الخروج -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-3 px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 w-full">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </button>
            </form>
        </nav>
    </aside>
    
</div>

<script>
    function handleHexColorChangeMobile(val) {
        let hex = val.trim();
        if (hex[0] !== '#') hex = '#' + hex;
        document.getElementById('customHexColorMobile').value = hex;
        document.getElementById('customHexInputMobile').value = hex;
    }

    function submitHexColorMobile() {
        let hex = document.getElementById('customHexInputMobile').value.trim();
        if (hex[0] !== '#') hex = '#' + hex;
        if (/^#([A-Fa-f0-9]{6})$/.test(hex)) {
            updateTheme(hex);
        } else {
            alert('يرجى إدخال كود لون HEX صالح مثل #1abc9c');
        }
    }
</script>