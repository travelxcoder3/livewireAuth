<!-- لوحة التحكم الشاملة لأدمن الوكالة -->
@php $stats = $this->comprehensiveStats; @endphp

<!-- روابط سريعة لجميع الواجهات الخاصة بأدمن الوكالة -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <a href="{{ route('agency.users') }}" class="bg-blue-500 hover:bg-blue-600 text-white rounded-lg p-4 flex flex-col items-center shadow">
        <i class="fas fa-users fa-2x mb-2"></i>
        <span>المستخدمين</span>
    </a>
    <a href="{{ route('agency.roles') }}" class="bg-purple-500 hover:bg-purple-600 text-white rounded-lg p-4 flex flex-col items-center shadow">
        <i class="fas fa-user-tag fa-2x mb-2"></i>
        <span>الأدوار</span>
    </a>
    <a href="{{ route('agency.permissions') }}" class="bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg p-4 flex flex-col items-center shadow">
        <i class="fas fa-shield-alt fa-2x mb-2"></i>
        <span>الصلاحيات</span>
    </a>
    <a href="{{ route('agency.service_types') }}" class="bg-green-500 hover:bg-green-600 text-white rounded-lg p-4 flex flex-col items-center shadow">
        <i class="fas fa-concierge-bell fa-2x mb-2"></i>
        <span>الخدمات</span>
    </a>
    <a href="{{ route('agency.providers') }}" class="bg-pink-500 hover:bg-pink-600 text-white rounded-lg p-4 flex flex-col items-center shadow">
        <i class="fas fa-briefcase fa-2x mb-2"></i>
        <span>المزودين</span>
    </a>
    <a href="{{ route('agency.customers.add') }}" class="bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg p-4 flex flex-col items-center shadow">
        <i class="fas fa-user-plus fa-2x mb-2"></i>
        <span>إضافة عميل</span>
    </a>
    <a href="{{ route('agency.hr.employees.index') }}" class="bg-teal-500 hover:bg-teal-600 text-white rounded-lg p-4 flex flex-col items-center shadow">
        <i class="fas fa-user-tie fa-2x mb-2"></i>
        <span>الموظفين</span>
    </a>
    <a href="{{ route('agency.sales.index') }}" class="bg-orange-500 hover:bg-orange-600 text-white rounded-lg p-4 flex flex-col items-center shadow">
        <i class="fas fa-chart-line fa-2x mb-2"></i>
        <span>المبيعات</span>
    </a>
    <a href="{{ route('agency.profile') }}" class="bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg p-4 flex flex-col items-center shadow">
        <i class="fas fa-user-circle fa-2x mb-2"></i>
        <span>الملف الشخصي</span>
    </a>
</div>

<!-- إحصائيات شاملة -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
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
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-user-tag text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['roles_count'] }}</h3>
                <p class="text-gray-600">عدد الأدوار</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-shield-alt text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['permissions_count'] }}</h3>
                <p class="text-gray-600">عدد الصلاحيات</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-pink-100 text-pink-600">
                <i class="fas fa-star text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['most_used_role'] ? $stats['most_used_role']->name : '-' }}</h3>
                <p class="text-gray-600">أكثر دور مستخدم</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-black mb-4">آخر الأدوار المضافة</h2>
        <ul class="space-y-2">
            @foreach($this->recentRoles as $role)
                <li class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">{{ $role->name }}</span>
                    <span class="text-xs text-gray-500">{{ $role->created_at->format('Y-m-d') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-black mb-4">آخر الصلاحيات المضافة</h2>
        <ul class="space-y-2">
            @foreach($this->recentPermissions as $permission)
                <li class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">{{ $permission->name }}</span>
                    <span class="text-xs text-gray-500">{{ $permission->created_at->format('Y-m-d') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-8">
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

<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-black mb-4">أكثر صلاحية مستخدمة (مرتبطة بأدوار)</h2>
    @if($stats['most_used_permission'])
        <div class="flex items-center">
            <span class="font-medium text-blue-700">{{ $stats['most_used_permission']->name }}</span>
            <span class="ml-4 text-xs text-gray-500">مرتبطة بـ {{ $stats['most_used_permission']->roles_count }} دور</span>
        </div>
    @else
        <p class="text-gray-500">لا توجد صلاحيات مرتبطة بأدوار بعد.</p>
    @endif
</div>

<!-- آخر المستخدمين -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-black mb-4">آخر المستخدمين المضافين</h2>
    @if($this->recentUsers->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">الاسم</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">البريد الإلكتروني</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">الدور</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider">الحالة</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($this->recentUsers as $user)
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500 text-center py-4">لا يوجد مستخدمين بعد</p>
    @endif
</div> 