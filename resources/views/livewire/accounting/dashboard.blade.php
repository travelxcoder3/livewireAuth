<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">مرحباً بك في قسم الحسابات</h1>
        <p class="text-gray-600">لوحة تحكم موظف الحسابات - {{ Auth::user()->name }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- بطاقة التقارير المالية -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold mb-2">التقارير المالية</h3>
                    <p class="text-emerald-100 text-sm">إدارة وتصفح التقارير المالية</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <a href="{{ route('accounting.financial-reports') }}" class="inline-block mt-4 bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm font-medium transition">
                عرض التقارير
            </a>
        </div>

        <!-- بطاقة ملفات العملاء -->
        <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold mb-2">ملفات العملاء</h3>
                    <p class="text-teal-100 text-sm">إدارة الملفات المالية للعملاء</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2h5"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
            </div>
            <a href="{{ route('accounting.customer-files') }}" class="inline-block mt-4 bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm font-medium transition">
                عرض الملفات
            </a>
        </div>

        <!-- بطاقة ملفات الموردين -->
        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold mb-2">ملفات الموردين</h3>
                    <p class="text-cyan-100 text-sm">إدارة الملفات المالية للموردين</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
            <a href="{{ route('accounting.provider-files') }}" class="inline-block mt-4 bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm font-medium transition">
                عرض الملفات
            </a>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">إحصائيات سريعة</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-emerald-50 rounded-lg">
                <div class="text-2xl font-bold text-emerald-600">0</div>
                <div class="text-sm text-gray-600">التقارير المالية</div>
            </div>
            <div class="text-center p-4 bg-teal-50 rounded-lg">
                <div class="text-2xl font-bold text-teal-600">0</div>
                <div class="text-sm text-gray-600">ملفات العملاء</div>
            </div>
            <div class="text-center p-4 bg-cyan-50 rounded-lg">
                <div class="text-2xl font-bold text-cyan-600">0</div>
                <div class="text-sm text-gray-600">ملفات الموردين</div>
            </div>
            <div class="text-center p-4 bg-emerald-50 rounded-lg">
                <div class="text-2xl font-bold text-emerald-600">0</div>
                <div class="text-sm text-gray-600">المعاملات اليوم</div>
            </div>
        </div>
    </div>
</div> 