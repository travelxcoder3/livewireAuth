<div>
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">إدارة الأدوار والصلاحيات</h1>
                <p class="text-gray-600">إنشاء وإدارة الأدوار وصلاحياتها في وكالتك</p>
            </div>
            @can('roles.create')
            <button wire:click="$set('showAddModal', true)" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus ml-2"></i>
                إضافة دور جديد
            </button>
            @endcan
        </div>
    </div>

    <!-- قائمة الأدوار -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">الأدوار الموجودة</h2>
        </div>
        
        @if($roles->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم الدور</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الصلاحيات</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد المستخدمين</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($roles as $role)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900">{{ $role->name }}</div>
                                        @if(in_array($role->name, ['super-admin', 'agency-admin']))
                                            <span class="mr-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                أساسي
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($role->permissions as $permission)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $permission->name }}
                                            </span>
                                        @endforeach
                                        @if($role->permissions->count() == 0)
                                            <span class="text-gray-500 text-sm">لا توجد صلاحيات</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $role->users_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2 space-x-reverse">
                                        @can('roles.edit')
                                        <button wire:click="editRole({{ $role->id }})" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @endcan
                                        @can('roles.delete')
                                        @if(!in_array($role->name, ['super-admin', 'agency-admin']))
                                            <button wire:click="deleteRole({{ $role->id }})" 
                                                    onclick="return confirm('هل أنت متأكد من حذف هذا الدور؟')"
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
                <i class="fas fa-user-tag text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">لا توجد أدوار بعد</p>
                <button wire:click="$set('showAddModal', true)" 
                        class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    إضافة أول دور
                </button>
            </div>
        @endif
    </div>

    <!-- Modal إضافة دور جديد -->
    @if($showAddModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">إضافة دور جديد</h3>
                    
                    <form wire:submit.prevent="addRole">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">اسم الدور</label>
                            <input type="text" wire:model="name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">الصلاحيات</label>
                            <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-md p-2">
                                @foreach($permissions as $permission)
                                    <label class="flex items-center mb-2">
                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="mr-2 text-sm text-gray-700">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedPermissions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-2 space-x-reverse">
                            <button type="button" wire:click="$set('showAddModal', false)" 
                                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                إلغاء
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                إضافة الدور
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal تعديل الدور -->
    @if($showEditModal && $editingRole)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تعديل الدور</h3>
                    
                    <form wire:submit.prevent="updateRole">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">اسم الدور</label>
                            <input type="text" wire:model="edit_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('edit_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">الصلاحيات</label>
                            <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-md p-2">
                                @foreach($permissions as $permission)
                                    <label class="flex items-center mb-2">
                                        <input type="checkbox" wire:model="edit_selectedPermissions" value="{{ $permission->name }}"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="mr-2 text-sm text-gray-700">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('edit_selectedPermissions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-2 space-x-reverse">
                            <button type="button" wire:click="$set('showEditModal', false)" 
                                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                إلغاء
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                تحديث الدور
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div> 