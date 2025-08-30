@php
use Illuminate\Support\Facades\DB;

// هدف الشهر الحالي: من EmployeeMonthlyTarget ثم fallback إلى users.main_target
$userTarget = \App\Models\EmployeeMonthlyTarget::where('user_id', Auth::id())
    ->where('year', now()->year)
    ->where('month', now()->month)
    ->value('main_target');

$userTarget = $userTarget !== null ? (float)$userTarget : (float)(Auth::user()->main_target ?? 0);

// الربح الشهري للمستخدم = SUM(usd_sell - usd_buy)
$userMonthlyProfit = \App\Models\Sale::where('user_id', Auth::id())
    ->whereMonth('sale_date', now()->month)
    ->whereYear('sale_date', now()->year)
    ->sum(DB::raw('COALESCE(usd_sell,0) - COALESCE(usd_buy,0)'));

$targetPercentage = $userTarget > 0 ? min((max($userMonthlyProfit,0) / $userTarget) * 100, 100) : 0;
@endphp

<div class="space-y-6">
    <!-- بياناتي الشخصية -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <!-- رأس القسم -->
        <div class="px-6 py-4" style="background: linear-gradient(135deg, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">بياناتي</h1>
                        <p class="text-white/80 text-sm">إحصائيات خاصة بك فقط</p>
                    </div>
                </div>
                <div class="text-white/60 text-sm">
                    <i class="fas fa-clock mr-1"></i>
                    آخر تحديث: {{ now()->format('Y-m-d H:i') }}
                </div>
            </div>
        </div>

        <!-- محتوى البيانات -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- اسم المستخدم -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-blue-600 font-medium">الاسم</p>
                            <p class="text-lg font-bold text-blue-800">{{ Auth::user()->name }}</p>
                        </div>
                    </div>
                </div>

                <!-- البريد -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-envelope text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-green-600 font-medium">البريد الإلكتروني</p>
                            <p class="text-lg font-bold text-green-800">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                </div>

                <!-- تاريخ الانضمام -->
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-4 border border-orange-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-orange-600 font-medium">تاريخ الانضمام</p>
                            <p class="text-lg font-bold text-orange-800">{{ Auth::user()->created_at->format('Y-m-d') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- كروت الإحصائيات -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-stat-card title="إجمالي المبيعات" icon="shopping-cart" color="primary"
            :value="\App\Models\Sale::where('user_id', Auth::id())->count()" />

        <x-stat-card title="مبيعات اليوم" icon="calendar-day" color="green"
            :value="\App\Models\Sale::where('user_id', Auth::id())->whereDate('sale_date', today())->count()" />

        <x-stat-card title="المبيعات الشهرية" icon="chart-line" color="blue"
            :value="\App\Models\Sale::where('user_id', Auth::id())->whereMonth('sale_date', now()->month)->whereYear('sale_date', now()->year)->count()" />

        <x-stat-card title="المبيعات الأسبوعية" icon="calendar-week" color="purple"
            :value="\App\Models\Sale::where('user_id', Auth::id())->whereBetween('sale_date', [now()->startOfWeek(), now()->endOfWeek()])->count()" />
    </div>

    <!-- الهدف الأساسي -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-start justify-between">
            <div class="p-3 rounded-lg mr-3"
                style="background-color: rgba(var(--orange-100), 0.5); color: rgb(var(--orange-600));">
                <i class="fas fa-bullseye text-lg"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600">الهدف الأساسي</p>
                <p class="text-2xl font-bold mt-1" style="color: rgb(var(--orange-600));">
                    ${{ number_format($userTarget, 0) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    محقق (ربح): ${{ number_format($userMonthlyProfit, 0) }} ({{ number_format($targetPercentage, 1) }}%)
                </p>

                <div class="mt-2 h-1 rounded-full overflow-hidden bg-gray-100">
                    <div class="h-full transition-all duration-300"
                        style="width: {{ $targetPercentage }}%; background-color: rgb(var(--orange-500));"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات المبيعات من لوحة التحكم -->
    @include('livewire.agency.dashboard.partials.sales-stats')

    <!-- الإجراءات السريعة -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">إجراءات سريعة</h2>
            <div class="text-sm" style="color: rgb(var(--primary-500));">إدارة المبيعات</div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @php
                $salesQuickLinks = [
                    ['route' => 'agency.sales.index', 'icon' => 'list-alt', 'title' => 'عرض جميع المبيعات', 'desc' => 'عرض كافة عمليات البيع المسجلة'],
                    ['route' => 'agency.sales.index', 'icon' => 'plus-circle', 'title' => 'إضافة عملية بيع', 'desc' => 'تسجيل عملية بيع جديدة'],
                    ['route' => 'agency.sales.report.pdf', 'icon' => 'file-pdf', 'title' => 'تقرير PDF', 'desc' => 'إنشاء تقرير PDF للمبيعات'],
                    ['route' => 'agency.sales.report.excel', 'icon' => 'file-excel', 'title' => 'تقرير Excel', 'desc' => 'إنشاء تقرير Excel للمبيعات'],
                ];
            @endphp

            @foreach($salesQuickLinks as $link)
                @if(Route::has($link['route']))
                    <a href="{{ route($link['route']) }}" 
                       class="group flex items-center gap-4 p-4 rounded-xl transition-all duration-200 hover:shadow-md border border-gray-100 hover:border-transparent"
                       style="background-color: rgba(var(--primary-50));">
                        <div class="flex-shrink-0 p-3 rounded-lg mr-4 transition-all duration-200 group-hover:scale-110"
                             style="background-color: rgba(var(--primary-500), 0.1); color: rgb(var(--primary-500));">
                            <i class="fas fa-{{ $link['icon'] }} text-lg"></i>
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
                @endif
            @endforeach
        </div>
    </div>
</div>
