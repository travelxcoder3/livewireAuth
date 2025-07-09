<div class="py-8 px-2 sm:px-6 lg:px-12 xl:px-24">
    <!-- عنوان الداشبورد -->
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-extrabold text-black flex items-center gap-3">
            <svg class="h-9 w-9 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke-width="2" />
            </svg>
            لوحة التحكم الرئيسية
        </h1>
        <span class="text-lg text-gray-700 font-semibold">مرحباً بك في نظام إدارة الوكالات</span>
    </div>
    <!-- صف الكروت والرسم البياني -->
    <div class="flex flex-col lg:flex-row gap-8 items-stretch">
        <!-- الرسم البياني على اليمين -->
        <div class="w-full lg:w-1/4 flex justify-center lg:justify-start">
            <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-xs flex flex-col items-center h-full min-h-[320px]">
                <h2 class="text-xl font-extrabold text-black mb-4 flex items-center gap-2 justify-center lg:justify-start">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full border-2 border-emerald-400">
                        <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2" />
                        </svg>
                    </span>
                    توزيع حالة الوكالات
                </h2>
                <div class="flex flex-col items-center w-full">
                    <div class="flex justify-center mb-4">
                        <div class="w-36 h-36">
                            <canvas id="agenciesStatusChart" width="144" height="144" style="width:144px; height:144px; max-width:100%;"></canvas>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 w-full mt-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-5 h-5 rounded-full border-2" style="background: rgb(var(--primary-500)); border-color: rgb(var(--primary-500));"></span>
                            <span class="font-semibold" style="color: rgb(var(--primary-500));">نشطة</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-5 h-5 rounded-full border-2" style="background: rgb(var(--primary-100)); border-color: rgb(var(--primary-100));"></span>
                            <span class="font-semibold" style="color: rgb(var(--primary-100));">معلقة</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-5 h-5 rounded-full border-2" style="background: rgb(var(--primary-600)); border-color: rgb(var(--primary-600));"></span>
                            <span class="font-semibold" style="color: rgb(var(--primary-600));">غير نشطة</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- الكروت على اليسار -->
        <div class="w-full lg:w-3/4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 h-full">
                <!-- كرت مدراء الوكالات -->
                <div class="group rounded-3xl shadow-2xl p-6 flex flex-col items-center justify-center gap-4 transition-transform duration-200 hover:scale-105 cursor-pointer min-h-[220px] h-full" style="background: linear-gradient(135deg, rgb(var(--primary-100)), #fff); border-top: 4px solid rgb(var(--primary-500));">
                    <div class="rounded-full p-5 shadow-lg mb-2" style="background: rgb(var(--primary-500)); color: var(--theme-on-primary);">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M20 8v6M23 11h-6"/>
                        </svg>
                    </div>
                    <div class="text-4xl font-extrabold text-black drop-shadow">{{ $totalAgencyAdmins }}</div>
                    <div class="text-gray-900 text-base font-semibold tracking-wide">مدراء الوكالات</div>
                </div>
                <!-- كرت الوكالات المعلقة -->
                <div class="group rounded-3xl shadow-2xl p-6 flex flex-col items-center justify-center gap-4 transition-transform duration-200 hover:scale-105 cursor-pointer min-h-[220px] h-full" style="background: linear-gradient(135deg, rgb(var(--primary-100)), #fff); border-top: 4px solid rgb(var(--primary-500));">
                    <div class="rounded-full p-5 shadow-lg mb-2" style="background: rgb(var(--primary-500)); color: var(--theme-on-primary);">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-4xl font-extrabold text-black drop-shadow">{{ $pendingAgencies }}</div>
                    <div class="text-gray-900 text-base font-semibold tracking-wide">الوكالات المعلقة</div>
                </div>
                <!-- كرت الوكالات النشطة -->
                <div class="group rounded-3xl shadow-2xl p-6 flex flex-col items-center justify-center gap-4 transition-transform duration-200 hover:scale-105 cursor-pointer min-h-[220px] h-full" style="background: linear-gradient(135deg, rgb(var(--primary-100)), #fff); border-top: 4px solid rgb(var(--primary-500));">
                    <div class="rounded-full p-5 shadow-lg mb-2" style="background: rgb(var(--primary-500)); color: var(--theme-on-primary);">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-4xl font-extrabold text-black drop-shadow">{{ $activeAgencies }}</div>
                    <div class="text-gray-900 text-base font-semibold tracking-wide">الوكالات النشطة</div>
                </div>
                <!-- كرت إجمالي الوكالات -->
                <div class="group rounded-3xl shadow-2xl p-6 flex flex-col items-center justify-center gap-4 transition-transform duration-200 hover:scale-105 cursor-pointer min-h-[220px] h-full" style="background: linear-gradient(135deg, rgb(var(--primary-100)), #fff); border-top: 4px solid rgb(var(--primary-500));">
                    <div class="rounded-full p-5 shadow-lg mb-2" style="background: rgb(var(--primary-500)); color: var(--theme-on-primary);">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="text-4xl font-extrabold text-black drop-shadow">{{ $totalAgencies }}</div>
                    <div class="text-gray-900 text-base font-semibold tracking-wide">إجمالي الوكالات</div>
                </div>
                <!-- كرت الوكالات غير النشطة -->
                <div class="group rounded-3xl shadow-2xl p-6 flex flex-col items-center justify-center gap-4 transition-transform duration-200 hover:scale-105 cursor-pointer min-h-[220px] h-full" style="background: linear-gradient(135deg, rgb(var(--primary-100)), #fff); border-top: 4px solid rgb(var(--primary-500));">
                    <div class="rounded-full p-5 shadow-lg mb-2" style="background: rgb(var(--primary-500)); color: var(--theme-on-primary);">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div class="text-4xl font-extrabold text-black drop-shadow">{{ $inactiveAgencies }}</div>
                    <div class="text-gray-900 text-base font-semibold tracking-wide">الوكالات غير النشطة</div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول آخر الوكالات المضافة -->
    <div class="bg-white rounded-2xl shadow-xl p-6 mt-8">
        <h2 class="text-xl font-bold text-black mb-4 flex items-center gap-2">
            <svg class="h-6 w-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9"/>
            </svg>
            آخر الوكالات المضافة
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-right text-gray-900 font-semibold">اسم الوكالة</th>
                        <th class="px-4 py-2 text-right text-gray-900 font-semibold">الهاتف</th>
                        <th class="px-4 py-2 text-right text-gray-900 font-semibold">البريد الإلكتروني</th>
                        <th class="px-4 py-2 text-right text-gray-900 font-semibold">المدير</th>
                        <th class="px-4 py-2 text-right text-gray-900 font-semibold">الحالة</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($recentAgencies as $agency)
                        <tr class="hover:bg-emerald-50 transition">
                            <td class="px-4 py-2 font-bold text-black">{{ $agency->name }}</td>
                            <td class="px-4 py-2">{{ $agency->phone }}</td>
                            <td class="px-4 py-2">{{ $agency->email }}</td>
                            <td class="px-4 py-2">
                                @if($agency->admin)
                                    <span class="font-semibold text-black">{{ $agency->admin->name }}</span>
                                    <span class="block text-xs text-gray-700">{{ $agency->admin->email }}</span>
                                @else
                                    <span class="text-xs text-red-500">لم يتم تعيين مدير</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($agency->status === 'active')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">نشطة</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">معلقة</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-gray-400">لا توجد وكالات مضافة مؤخراً</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function getThemeColor(variable, fallback) {
        const val = getComputedStyle(document.documentElement).getPropertyValue(variable);
        return val ? 'rgb(' + val.trim() + ')' : fallback;
    }
    var ctx = document.getElementById('agenciesStatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['نشطة', 'معلقة', 'غير نشطة'],
            datasets: [{
                data: [{{ $activeAgencies }} , {{ $pendingAgencies }}, {{ $totalAgencies - $activeAgencies - $pendingAgencies }}],
                backgroundColor: [
                    getThemeColor('--primary-500', '#10b981'),
                    getThemeColor('--primary-100', '#facc15'),
                    getThemeColor('--primary-600', '#f87171')
                ],
                borderWidth: 2
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        font: { family: 'Tajawal, sans-serif', size: 14 }
                    }
                }
            }
        }
    });
});
</script>
