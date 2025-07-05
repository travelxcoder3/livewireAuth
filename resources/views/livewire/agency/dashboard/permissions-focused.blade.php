<!-- لوحة التحكم المخصصة لمدير الصلاحيات -->
@php $stats = $this->permissionsStats; @endphp

<!-- إحصائيات الصلاحيات -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-shield-alt text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['total_permissions'] }}</h3>
                <p class="text-gray-600">إجمالي الصلاحيات</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['used_permissions'] }}</h3>
                <p class="text-gray-600">صلاحيات مستخدمة</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-times-circle text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['unused_permissions'] }}</h3>
                <p class="text-gray-600">صلاحيات غير مستخدمة</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-chart-line text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['total_permissions'] > 0 ? round(($stats['used_permissions'] / $stats['total_permissions']) * 100, 1) : 0 }}%</h3>
                <p class="text-gray-600">نسبة الاستخدام</p>
            </div>
        </div>
    </div>
</div>

<!-- أكثر الصلاحيات استخداماً -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">أكثر الصلاحيات استخداماً</h2>
    @if($stats['top_permissions']->count() > 0)
        <div class="space-y-4">
            @foreach($stats['top_permissions'] as $permission)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700">{{ $permission->name }}</span>
                        <span class="mr-4 text-sm text-gray-500">({{ $permission->roles_count }} دور)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-32 bg-gray-200 rounded-full h-2 mr-4">
                            @php
                                $maxRoles = $stats['top_permissions']->max('roles_count');
                                $percentage = $maxRoles > 0 ? ($permission->roles_count / $maxRoles) * 100 : 0;
                            @endphp
                            <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                        <span class="text-sm text-gray-500">{{ round($percentage, 1) }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-center py-4">لا توجد صلاحيات بعد</p>
    @endif
</div>

<!-- آخر الصلاحيات المضافة -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">آخر الصلاحيات المضافة</h2>
    @if($stats['recent_permissions']->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم الصلاحية</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الأدوار</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الإنشاء</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($stats['recent_permissions'] as $permission)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $permission->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $permission->roles_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $permission->created_at->format('Y-m-d') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(($permission->roles_count ?? 0) > 0)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        مستخدمة
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        غير مستخدمة
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500 text-center py-4">لا توجد صلاحيات مضافة حديثاً</p>
    @endif
</div>

<!-- معلومات الوكالة -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">معلومات الوكالة</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-gray-600">اسم الوكالة</p>
            <p class="font-semibold">{{ $this->agencyInfo->name }}</p>
        </div>
        <div>
            <p class="text-gray-600">البريد الإلكتروني</p>
            <p class="font-semibold">{{ $this->agencyInfo->email }}</p>
        </div>
        <div>
            <p class="text-gray-600">رقم الترخيص</p>
            <p class="font-semibold">{{ $this->agencyInfo->license_number }}</p>
        </div>
        <div>
            <p class="text-gray-600">تاريخ انتهاء الترخيص</p>
            <p class="font-semibold">{{ $this->agencyInfo->license_expiry_date->format('Y-m-d') }}</p>
        </div>
    </div>
</div> 