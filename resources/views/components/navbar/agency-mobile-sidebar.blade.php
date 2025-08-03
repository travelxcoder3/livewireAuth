@php
    use App\Models\ApprovalRequest;
    $user = Auth::user();
    $agencyName = $user && $user->agency ? $user->agency->name : 'Travel-X';
    $mainAgency = Auth::user()->agency->parent_id ? Auth::user()->agency->parent : Auth::user()->agency;
    $agencyIds = $mainAgency->branches()->pluck('id')->toArray();
    $agencyIds[] = $mainAgency->id;
    $pendingApprovalsCount = ApprovalRequest::where('status', 'pending')
        ->whereIn('agency_id', $agencyIds)
        ->count();
@endphp

<div class="fixed inset-0 z-50 flex lg:hidden">
    <!-- خلفية شفافة -->
    <div class="fixed inset-0 bg-black bg-opacity-40" @click="mobileSidebarOpen = false"></div>
    
    <!-- القائمة الجانبية -->
    <aside class="relative w-72 max-w-full bg-white shadow-xl h-full flex flex-col rtl:border-l rtl:border-gray-200 ltr:border-r ltr:border-gray-200 overflow-y-auto max-h-screen">
        <!-- رأس القائمة -->
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo-travelx.png') }}" alt="TRAVEL-X Logo" class="h-10 w-auto object-contain">
                <div class="font-bold text-lg text-gray-800">{{ $agencyName }}</div>
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
            @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('sales.view'))
            <a href="{{ route('agency.sales.index') }}" @click="mobileSidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fas fa-cash-register"></i>
                <span>المبيعات</span>
            </a>
            @endif

            <!-- الحسابات -->
            @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('accounts.view') || Auth::user()->can('customers.view') || Auth::user()->can('providers.view'))
            <div x-data="{open: false}" class="relative">
                @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('accounts.view'))
                <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-wallet"></i>
                        <span>الحسابات</span>
                    </div>
                    <i class="fas" :class="{'fa-chevron-down': !open, 'fa-chevron-up': open}"></i>
                </button>
                @endif
                @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('accounts.view'))
                <div x-show="open" class="pl-4">
                    <a href="{{ route('agency.accounts') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>مراجعة الحسابات</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('customers.view'))
                    <a href="{{ route('agency.customers.add') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-folder-open"></i>
                        <span>ملفات العملاء</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('providers.view'))
                    <a href="{{ route('agency.providers') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-briefcase"></i>
                        <span>ملفات المزودين</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- التحصيل -->
            @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('collections.view'))
            <a href="{{ route('agency.collections') }}" @click="mobileSidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fas fa-hand-holding-usd"></i>
                <span>التحصيل</span>
            </a>
            @endif

            <!-- الموارد البشرية -->
            @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('employees.view') || Auth::user()->can('roles.view') || Auth::user()->can('users.view') || Auth::user()->can('permissions.view'))
            <div x-data="{open: false}" class="relative">
                <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-users"></i>
                        <span>الموارد البشرية</span>
                    </div>
                    <i class="fas" :class="{'fa-chevron-down': !open, 'fa-chevron-up': open}"></i>
                </button>
                @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('employees.view'))
                <div x-show="open" class="pl-4">
                    <a href="{{ route('agency.hr.employees.index') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-users"></i>
                        <span>الموظفين</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('roles.view'))
                    <a href="{{ route('agency.roles') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-user-shield"></i>
                        <span>الأدوار</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('users.view'))
                    <a href="{{ route('agency.users') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-user"></i>
                        <span>المستخدمين</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('permissions.view'))
                    <a href="{{ route('agency.permissions') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-key"></i>
                        <span>الصلاحيات</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- التقارير -->
            @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('reportsSales.view') || Auth::user()->can('reportsAccounts.view')
            || Auth::user()->can('reportCustomers.view'))
            <div x-data="{open: false}" class="relative">
                <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line"></i>
                        <span>التقارير</span>
                    </div>
                    <i class="fas" :class="{'fa-chevron-down': !open, 'fa-chevron-up': open}"></i>
                </button>
                <div x-show="open" class="pl-4">
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('reportsSales.view'))
                    <a href="{{ route('agency.reports.sales') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>تقارير المبيعات</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('reportsAccounts.view'))
                    <a href="{{ route('agency.reports.accounts') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-file-alt"></i>
                        <span>تقارير الحسابات</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('reportCustomers.view'))
                    <a href="{{ route('agency.reports.customers-follow-up') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-file-alt"></i>
                        <span> تقرير تتبع العملاء </span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- طلبات الموافقة -->
            @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('approvals.view'))
            <a href="{{ route('agency.approval-requests') }}" @click="mobileSidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fas fa-clipboard-check"></i>
                <span>طلبات الموافقة</span>
                @if($pendingApprovalsCount > 0)
                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                    {{ $pendingApprovalsCount }}
                </span>
                @endif
            </a>
            @endif

            <!-- إعدادات الشركة -->
             @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('profile.view') || Auth::user()->can('lists.view')|| Auth::user()->can('approval-sequences.view') || Auth::user()->can('commissions.view') || Auth::user()->can('policies.view') || Auth::user()->can('obligations.view'))
            <div x-data="{open: false}" class="relative">
                <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-cogs"></i>
                        <span>إعدادات الشركة</span>
                    </div>
                    <i class="fas" :class="{'fa-chevron-down': !open, 'fa-chevron-up': open}"></i>
                </button>
                @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('profile.view'))
                <div x-show="open" class="pl-4">
                    <a href="{{ route('agency.profile') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-building"></i>
                        <span>بيانات الشركة</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('lists.view'))
                    <a href="{{ route('agency.dynamic-lists') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-list-alt"></i>
                        <span>تهيئة القوائم</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('approval-sequences.view'))
                    <a href="{{ route('agency.approval-sequences') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-random"></i>
                        <span>تسلسل الموافقات</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('commissions.view'))
                    <a href="{{ route('agency.commissions') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-percentage"></i>
                        <span>تهيئة العمولات</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('policies.view'))
                    <a href="{{ route('agency.policies') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-file-contract"></i>
                        <span>سياسات الوكالة</span>
                    </a>
                    @endif
                    @if(Auth::user()->hasRole('agency-admin') || Auth::user()->can('obligations.view'))
                    <a href="{{ route('agency.obligations') }}" @click="mobileSidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-file-contract"></i>
                        <span>التزامات وقيود</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- التزامات وقيود (عرض فقط) -->
            <a href="{{ route('agency.obligations-view') }}" @click="mobileSidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fas fa-file-contract"></i>
                <span>التزامات وقيود</span>
            </a>

            <div class="border-t my-2"></div>

            <!-- إعدادات الثيم -->
            <div class="mb-2">
                <div class="flex items-center gap-3 px-3 py-2">
                    <i class="fas fa-palette"></i>
                    <span>تغيير الثيم</span>
                </div>
                @php
                    use App\Services\ThemeService;
                    $themes = ThemeService::getThemeColors();
                @endphp
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
                        class="border rounded px-2 py-1 text-xs w-20" onchange="handleHexColorChangeMobile(this.value)" />
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