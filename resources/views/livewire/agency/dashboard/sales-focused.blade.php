<div class="space-y-6">
    <!-- Sales Dashboard Header -->
    <div class="rounded-xl p-6 mb-6" style="background: linear-gradient(135deg, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">لوحة تحكم مبيعاتي</h1>
                <p class="text-white/90">
                    إحصائيات وتقارير مبيعاتك الشخصية - {{ Auth::user()->name }}
                </p>
            </div>
            <div class="mt-4 md:mt-0 bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-white text-sm">إجمالي مبيعاتك: <span class="font-bold">{{ \App\Models\Sale::where('agency_id', Auth::user()->agency_id)->where('user_id', Auth::user()->id)->count() }}</span></p>
                        <p class="text-white/80 text-xs mt-1">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            آخر تحديث: {{ now()->format('Y-m-d H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Sales Card -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between">
                <div class="p-3 rounded-lg mr-3"
                    style="background-color: rgba(var(--primary-100), 0.5); color: rgb(var(--primary-600));">
                    <i class="fas fa-shopping-cart text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">إجمالي المبيعات</p>
                    <p class="text-2xl font-bold mt-1" style="color: rgb(var(--primary-600));">
                        {{ \App\Models\Sale::where('agency_id', Auth::user()->agency_id)->count() }}
                    </p>
                </div>
            </div>
            <div class="mt-3 h-1 rounded-full overflow-hidden bg-gray-100">
                <div class="h-full" style="width: 100%; background-color: rgb(var(--primary-500));"></div>
            </div>
        </div>

        <!-- Today's Sales Card -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between">
                <div class="p-3 rounded-lg mr-3"
                    style="background-color: rgba(var(--green-100), 0.5); color: rgb(var(--green-600));">
                    <i class="fas fa-calendar-day text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">مبيعات اليوم</p>
                    <p class="text-2xl font-bold mt-1" style="color: rgb(var(--green-600));">
                        {{ \App\Models\Sale::where('agency_id', Auth::user()->agency_id)
                            ->whereDate('sale_date', today())
                            ->count() }}
                    </p>
                </div>
            </div>
            <div class="mt-3 h-1 rounded-full overflow-hidden bg-gray-100">
                <div class="h-full" style="width: 100%; background-color: rgb(var(--green-500));"></div>
            </div>
        </div>


        <!-- Monthly Sales Card -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between">
                <div class="p-3 rounded-lg mr-3"
                    style="background-color: rgba(var(--blue-100), 0.5); color: rgb(var(--blue-600));">
                    <i class="fas fa-chart-line text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">المبيعات الشهرية</p>
                    <p class="text-2xl font-bold mt-1" style="color: rgb(var(--blue-600));">
                        {{ \App\Models\Sale::where('agency_id', Auth::user()->agency_id)
                            ->whereMonth('sale_date', now()->month)
                            ->whereYear('sale_date', now()->year)
                            ->count() }}
                    </p>
                </div>
            </div>
            <div class="mt-3 h-1 rounded-full overflow-hidden bg-gray-100">
                <div class="h-full" style="width: 100%; background-color: rgb(var(--blue-500));"></div>
            </div>
        </div>

        <!-- Weekly Sales Card -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between">
                <div class="p-3 rounded-lg mr-3"
                    style="background-color: rgba(var(--purple-100), 0.5); color: rgb(var(--purple-600));">
                    <i class="fas fa-calendar-week text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600">المبيعات الأسبوعية</p>
                    <p class="text-2xl font-bold mt-1" style="color: rgb(var(--purple-600));">
                        {{ \App\Models\Sale::where('agency_id', Auth::user()->agency_id)
                            ->whereBetween('sale_date', [now()->startOfWeek(), now()->endOfWeek()])
                            ->count() }}
                    </p>
                </div>
            </div>
            <div class="mt-3 h-1 rounded-full overflow-hidden bg-gray-100">
                <div class="h-full" style="width: 100%; background-color: rgb(var(--purple-500));"></div>
            </div>
        </div>
    </div>

    <!-- إحصائيات المبيعات (منسوخة من لوحة التحكم الشاملة) -->
    @include('livewire.agency.dashboard.partials.sales-stats')

    <!-- Quick Actions -->
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
                            @switch($link['icon'])
                                @case('list-alt')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    @break
                                @case('plus-circle')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    @break
                                @case('file-pdf')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    @break
                                @case('file-excel')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    @break
                                @case('user-plus')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    @break
                                @case('briefcase')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
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
                @endif
            @endforeach
        </div>
    </div>
</div>