<div>
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">إدارة الصلاحيات</h1>
                <p class="text-gray-600">إنشاء وإدارة الصلاحيات في وكالتك</p>
            </div>
            @if($showAddButton)
                <div class="flex space-x-2 space-x-reverse">
                    @can('permissions.create')
                    <button wire:click="createBasicPermissions" 
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-magic ml-2"></i>
                        إنشاء الصلاحيات الأساسية
                    </button>
                    <button wire:click="$set('showAddModal', true)" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus ml-2"></i>
                        إضافة صلاحية جديدة
                    </button>
                    @endcan
                </div>
            @else
                <div class="text-green-600 bg-green-100 px-4 py-2 rounded-lg">
                    <i class="fas fa-check ml-2"></i>
                    جميع الصلاحيات الأساسية موجودة
                </div>
            @endif
        </div>
    </div>

    <!-- قائمة الصلاحيات -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">الصلاحيات الموجودة</h2>
        </div>
        
        @if($permissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم الصلاحية</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الأدوار</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النوع</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($permissions as $permission)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900">{{ $permission->name }}</div>
                                        @php
                                            $basicPermissions = [
                                                'users.view', 'users.create', 'users.edit', 'users.delete',
                                                'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                                                'permissions.view', 'permissions.manage'
                                            ];
                                        @endphp
                                        @if(in_array($permission->name, $basicPermissions))
                                            <span class="mr-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                أساسية
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $permission->roles_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $parts = explode('.', $permission->name);
                                        $module = $parts[0] ?? '';
                                        $action = $parts[1] ?? '';
                                    @endphp
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900">{{ ucfirst($module) }}</span>
                                        <span class="text-xs text-gray-500">{{ ucfirst($action) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2 space-x-reverse">
                                        @can('permissions.edit')
                                        <button wire:click="editPermission({{ $permission->id }})" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @endcan
                                        @can('permissions.delete')
                                        @if(!in_array($permission->name, $basicPermissions))
                                            <button wire:click="deletePermission({{ $permission->id }})" 
                                                    onclick="return confirm('هل أنت متأكد من حذف هذه الصلاحية؟')"
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-shield-alt text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">لا توجد صلاحيات بعد</p>
                <button wire:click="$set('showAddModal', true)" 
                        class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    إضافة أول صلاحية
                </button>
            </div>
        @endif
    </div>

    <!-- Modal إضافة صلاحية جديدة -->
    @if($showAddModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">إضافة صلاحية جديدة</h3>
                    
                    <form wire:submit.prevent="addPermission">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">اسم الصلاحية</label>
                            <input type="text" wire:model="name" 
                                   placeholder="مثال: services.create"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">استخدم التنسيق: module.action (مثال: services.create)</p>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-2 space-x-reverse">
                            <button type="button" wire:click="$set('showAddModal', false)" 
                                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                إلغاء
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                إضافة الصلاحية
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal تعديل الصلاحية -->
    @if($showEditModal && $editingPermission)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تعديل الصلاحية</h3>
                    
                    <form wire:submit.prevent="updatePermission">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">اسم الصلاحية</label>
                            <input type="text" wire:model="edit_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('edit_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-2 space-x-reverse">
                            <button type="button" wire:click="$set('showEditModal', false)" 
                                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                إلغاء
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                تحديث الصلاحية
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- دليل الصلاحيات -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-800 mb-4">دليل الصلاحيات</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h4 class="font-medium text-blue-700 mb-2">الوحدات الأساسية:</h4>
                <ul class="text-sm text-blue-600 space-y-1">
                    <li>• <strong>users</strong> - إدارة المستخدمين</li>
                    <li>• <strong>roles</strong> - إدارة الأدوار</li>
                    <li>• <strong>permissions</strong> - إدارة الصلاحيات</li>
                    <li>• <strong>services</strong> - إدارة الخدمات</li>
                    <li>• <strong>employees</strong> - إدارة الموظفين</li>
                    <li>• <strong>reports</strong> - التقارير</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-blue-700 mb-2">الإجراءات المتاحة:</h4>
                <ul class="text-sm text-blue-600 space-y-1">
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