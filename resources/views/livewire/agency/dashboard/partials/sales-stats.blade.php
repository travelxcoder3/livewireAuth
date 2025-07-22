@php
use App\Services\ThemeService;
$themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
$colors = ThemeService::getCurrentThemeColors($themeName);
$isAdmin = Auth::user()->hasRole('agency-admin');
@endphp

<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">
            @if($isAdmin)
                إحصائيات المبيعات - جميع الموظفين
            @else
                إحصائيات مبيعاتك الشخصية
            @endif
        </h2>
        <div class="flex gap-2">
            <button wire:click="updateStatsViewType('monthly')" 
                class="px-3 py-1 rounded-lg text-sm font-medium transition-all duration-200 {{ $statsViewType === 'monthly' ? 'text-white' : 'text-gray-600 hover:text-gray-800' }}"
                style="{{ $statsViewType === 'monthly' ? 'background-color: rgb(var(--primary-500));' : 'background-color: rgb(var(--gray-100));' }}">
                شهري
            </button>
            <button wire:click="updateStatsViewType('service')" 
                class="px-3 py-1 rounded-lg text-sm font-medium transition-all duration-200 {{ $statsViewType === 'service' ? 'text-white' : 'text-gray-600 hover:text-gray-800' }}"
                style="{{ $statsViewType === 'service' ? 'background-color: rgb(var(--primary-500));' : 'background-color: rgb(var(--gray-100));' }}">
                حسب الخدمة
            </button>
            @if($isAdmin)
            <button wire:click="updateStatsViewType('employee')" 
                class="px-3 py-1 rounded-lg text-sm font-medium transition-all duration-200 {{ $statsViewType === 'employee' ? 'text-white' : 'text-gray-600 hover:text-gray-800' }}"
                style="{{ $statsViewType === 'employee' ? 'background-color: rgb(var(--primary-500));' : 'background-color: rgb(var(--gray-100));' }}">
                حسب الموظف
            </button>
            @endif
        </div>
    </div>

    @if($statsViewType === 'monthly')
        <!-- عرض الإحصائيات الشهرية -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($salesByMonth as $month)
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-800 mb-2">
                        {{ DateTime::createFromFormat('!m', $month['month'])->format('F') }} {{ $month['year'] }}
                    </h4>
                    <p class="text-sm text-gray-600">المبيعات: <span class="font-bold text-green-600">${{ number_format($month['total_sales'], 2) }}</span></p>
                    <p class="text-sm text-gray-600">العمليات: <span class="font-bold">{{ $month['operations_count'] }}</span></p>
                </div>
            @endforeach
        </div>
    @elseif($statsViewType === 'service')
        <!-- عرض الإحصائيات حسب الخدمة -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($salesByService as $service)
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-800 mb-2">{{ $service['service_type'] }}</h4>
                    <p class="text-sm text-gray-600">المبيعات: <span class="font-bold text-green-600">${{ number_format($service['total_sales'], 2) }}</span></p>
                    <p class="text-sm text-gray-600">العمليات: <span class="font-bold">{{ $service['operations_count'] }}</span></p>
                </div>
            @endforeach
        </div>
    @elseif($statsViewType === 'employee' && $isAdmin)
        <!-- عرض الإحصائيات حسب الموظف (للأدمن فقط) -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            اسم الموظف
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            إجمالي المبيعات
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            عدد العمليات
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            متوسط العملية
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($salesByEmployee as $employee)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                             style="background-color: rgb(var(--primary-500));">
                                            {{ substr($employee['employee'], 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="mr-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $employee['employee'] }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                ${{ number_format($employee['total_sales'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $employee['operations_count'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ $employee['operations_count'] > 0 ? number_format($employee['total_sales'] / $employee['operations_count'], 2) : '0.00' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                لا توجد بيانات لعرضها
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if($statsViewType === 'monthly' && count($salesByMonth) === 0)
        <div class="text-center py-8">
            <div class="text-gray-400 text-lg mb-2">📊</div>
            <p class="text-gray-500">لا توجد بيانات مبيعات لعرضها</p>
        </div>
    @endif
</div>
