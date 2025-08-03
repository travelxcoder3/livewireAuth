<!-- لوحة التحكم المخصصة لمدير المستخدمين -->
<div>
@php $stats = $this->usersStats; @endphp

<!-- إحصائيات المستخدمين -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['total_users'] }}</h3>
                <p class="text-gray-600">إجمالي المستخدمين</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-user-check text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['active_users'] }}</h3>
                <p class="text-gray-600">المستخدمين النشطين</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-user-times text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['inactive_users'] }}</h3>
                <p class="text-gray-600">المستخدمين غير النشطين</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-chart-pie text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100, 1) : 0 }}%</h3>
                <p class="text-gray-600">نسبة النشاط</p>
            </div>
        </div>
    </div>
</div>

<!-- توزيع المستخدمين حسب الأدوار -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold text-black mb-4">توزيع المستخدمين حسب الأدوار</h2>
    @if($stats['users_by_role']->count() > 0)
        <div class="space-y-4">
            @foreach($stats['users_by_role'] as $roleName => $users)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-900">{{ $roleName }}</span>
                        <span class="mr-4 text-sm text-gray-500">({{ $users->count() }} مستخدم)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-32 bg-gray-200 rounded-full h-2 mr-4">
                            @php
                                $maxUsers = $stats['users_by_role']->map->count()->max();
                                $percentage = $maxUsers > 0 ? ($users->count() / $maxUsers) * 100 : 0;
                            @endphp
                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                        <span class="text-sm text-gray-500">{{ round($percentage, 1) }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-center py-4">لا توجد مستخدمين بعد</p>
    @endif
</div>

<!-- آخر المستخدمين المضافين -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold text-black mb-4">آخر المستخدمين المضافين</h2>
    @if($stats['recent_users']->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">الاسم</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">البريد الإلكتروني</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">الدور</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">الحالة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">تاريخ الإنشاء</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($stats['recent_users'] as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $user->roles->first()->name ?? 'غير محدد' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->is_active)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        نشط
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        غير نشط
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $user->created_at->format('Y-m-d') }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500 text-center py-4">لا يوجد مستخدمين بعد</p>
    @endif
</div>

<!-- معلومات الوكالة -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-black mb-4">معلومات الوكالة</h2>
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
</div>