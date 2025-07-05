<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">مرحباً بك في نظام إدارة الوكالات</h1>
        <p class="text-gray-600">لوحة تحكم مدير النظام لإدارة وكالات السفر</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Agencies -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">إجمالي الوكالات</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalAgencies }}</p>
                </div>
            </div>
        </div>

        <!-- Active Agencies -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">الوكالات النشطة</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $activeAgencies }}</p>
                </div>
            </div>
        </div>

        <!-- Pending Agencies -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">الوكالات المعلقة</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $pendingAgencies }}</p>
                </div>
            </div>
        </div>

        <!-- Agency Admins -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">مديري الوكالات</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalAgencyAdmins }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">إجراءات سريعة</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('admin.add-agency') }}" 
               class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-emerald-50 hover:border-emerald-300 transition duration-200">
                <div class="p-2 rounded-full bg-emerald-100 text-emerald-600 mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">إضافة وكالة جديدة</h3>
                    <p class="text-sm text-gray-600">إضافة وكالة سفر جديدة مع تعيين مدير لها</p>
                </div>
            </a>
            
            <a href="{{ route('admin.agencies') }}" 
               class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-emerald-50 hover:border-emerald-300 transition duration-200">
                <div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">عرض جميع الوكالات</h3>
                    <p class="text-sm text-gray-600">إدارة وتعديل الوكالات الموجودة</p>
                </div>
            </a>
            
            <button wire:click="createPermissionsForAllAgencies" 
                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-purple-50 hover:border-purple-300 transition duration-200">
                <div class="p-2 rounded-full bg-purple-100 text-purple-600 mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">إنشاء الصلاحيات الأساسية</h3>
                    <p class="text-sm text-gray-600">إنشاء الصلاحيات والأدوار لجميع الوكالات</p>
                </div>
            </button>
            
            <button wire:click="createPermissionsForExistingAgencies" 
                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-orange-50 hover:border-orange-300 transition duration-200">
                <div class="p-2 rounded-full bg-orange-100 text-orange-600 mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">إنشاء الصلاحيات للوكالات الموجودة</h3>
                    <p class="text-sm text-gray-600">إنشاء الصلاحيات والأدوار للوكالات الموجودة فقط</p>
                </div>
            </button>
        </div>
    </div>

    <!-- Recent Agencies -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">آخر الوكالات المضافة</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            اسم الوكالة
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            البريد الإلكتروني
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            مدير الوكالة
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            الحالة
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            تاريخ الإضافة
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentAgencies as $agency)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $agency->name }}</div>
                                <div class="text-sm text-gray-500">{{ $agency->phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $agency->email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($agency->admin)
                                    <div class="text-sm font-medium text-gray-900">{{ $agency->admin->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $agency->admin->email }}</div>
                                @else
                                    <span class="text-sm text-red-500">لم يتم تعيين مدير</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($agency->status === 'active')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        نشطة
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        معلقة
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $agency->created_at->format('Y-m-d') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                لا توجد وكلات مسجلة حالياً
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
