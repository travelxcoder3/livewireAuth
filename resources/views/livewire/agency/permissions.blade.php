<div>
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">عرض الصلاحيات</h1>
                <p class="text-gray-600">عرض الصلاحيات المتاحة في النظام</p>
            </div>
            <div class="text-[rgb(var(--primary-600))] bg-[rgba(var(--primary-100),0.25)] border border-theme px-4 py-2 rounded-lg flex items-center gap-2">
                <i class="fas fa-shield-alt ml-2 text-[rgb(var(--primary-500))]"></i>
                الصلاحيات ثابتة من قاعدة البيانات
            </div>
        </div>
    </div>

    <!-- قائمة الصلاحيات -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">الصلاحيات المتاحة في النظام</h2>
        </div>
        
        @if($permissions->count() > 0)
            @php
                $grouped = $permissions->groupBy(function($item) {
                    return explode('.', $item->name)[0] ?? 'أخرى';
                });
            @endphp

            @php
                $iconMap = [
                    'users' => 'fa-user-friends',
                    'roles' => 'fa-user-shield',
                    'permissions' => 'fa-key',
                    'service_types' => 'fa-cogs',
                    'employees' => 'fa-id-badge',
                    'reports' => 'fa-chart-bar',
                    'settings' => 'fa-cog',
                    'customers' => 'fa-users',
                    'sales' => 'fa-cash-register',
                    'departments' => 'fa-building',
                    'branches' => 'fa-code-branch',
                    'providers' => 'fa-truck',
                    'collections' => 'fa-archive',
                    'dynamic_lists' => 'fa-list',
                    'intermediaries' => 'fa-user-tie',
                    'default' => 'fa-layer-group',
                ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
            @foreach($grouped as $module => $perms)
                <div class="bg-white/80 border border-theme rounded-2xl shadow-xl p-4 flex flex-col transition hover:shadow-2xl hover:scale-[1.02] duration-200">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-[rgb(var(--primary-600))]">
                        <i class="fas {{ $iconMap[$module] ?? $iconMap['default'] }} text-[rgb(var(--primary-500))] text-xl"></i>
                        <span>قسم: {{ __(ucfirst($module)) }}</span>
                    </h3>
            <div class="overflow-x-auto">
                        <table class="min-w-full divide-y rounded-xl overflow-hidden divide-gray-200">
                        <thead class="bg-gray-100 text-gray-600">
                                <tr>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider">اسم الصلاحية</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($perms as $permission)
                            <tr>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900">{{ $permission->name }}</div>
                                        @php
                                            $basicPermissions = [
                                                'users.view', 'users.create', 'users.edit',
                                                'roles.view', 'roles.create', 'roles.edit','roles.delete',
                                                'permissions.view',
                                            ];
                                        @endphp
                                        @if(in_array($permission->name, $basicPermissions))
                                            <span class="mr-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                أساسية
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                        <td class="px-4 py-2 whitespace-nowrap">
                                    @php
                                        $parts = explode('.', $permission->name);
                                        $action = $parts[1] ?? '';
                                    @endphp
                                            <span class="text-xs font-semibold text-[rgb(var(--primary-500))]">{{ ucfirst($action) }}</span>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
                    </div>
                </div>
            @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-shield-alt text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">لا توجد صلاحيات في قاعدة البيانات</p>
            </div>
        @endif
    </div>



    <!-- دليل الصلاحيات -->
    <div class="mt-8 rounded-lg p-6" style="background: rgba(var(--primary-100), 0.15); border: 1px solid rgb(var(--primary-200));">
        <h3 class="text-lg font-semibold text-black mb-4">دليل الصلاحيات المتاحة</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h4 class="font-medium text-black mb-2">
الوحدات الأساسية:</h4>
                <ul class="text-sm text-black space-y-1">
                    <li>• <strong>users</strong> - إدارة المستخدمين</li>
                    <li>• <strong>roles</strong> - إدارة الأدوار</li>
                    <li>• <strong>permissions</strong> - إدارة الصلاحيات</li>
                    <li>• <strong>service_types</strong> - إدارة أنواع الخدمات</li>
                    <li>• <strong>employees</strong> - إدارة الموظفين</li>
                    <li>• <strong>reports</strong> - التقارير</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-black mb-2">
الإجراءات المتاحة:</h4>
                <ul class="text-sm text-black space-y-1">
                    <li>• <strong>view</strong> - عرض</li>
                    <li>• <strong>create</strong> - إنشاء</li>
                    <li>• <strong>edit</strong> - تعديل</li>
                    <li>• <strong>delete</strong> - حذف</li>
                    <li>• <strong>manage</strong> - إدارة شاملة</li>
                </ul>
            </div>
        </div>
    </div>
</div>