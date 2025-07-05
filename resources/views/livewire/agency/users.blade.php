<div>
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">إدارة المستخدمين</h1>
                <p class="text-gray-600">إدارة مستخدمي وكالتك وأدوارهم</p>
            </div>
            @can('users.create')
            <button wire:click="$set('showAddModal', true)" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus ml-2"></i>
                إضافة مستخدم جديد
            </button>
            @endcan
        </div>
    </div>

    <!-- قائمة المستخدمين -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">المستخدمين</h2>
        </div>
        
        @if($users->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الاسم</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">البريد الإلكتروني</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الدور</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <span class="text-blue-600 font-medium">{{ substr($user->name, 0, 1) }}</span>
                                            </div>
                                        </div>
                                        <div class="mr-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($user->roles->count() > 0)
                                        @foreach($user->roles as $role)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-gray-500 text-sm">لا يوجد دور</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            نشط
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            غير نشط
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2 space-x-reverse">
                                        @can('users.edit')
                                        <button wire:click="editUser({{ $user->id }})" 
                                                class="text-blue-600 hover:text-blue-900" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @endcan
                                        @can('users.edit')
                                        <button wire:click="toggleUserStatus({{ $user->id }})" 
                                                class="{{ $user->is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' }}"
                                                title="{{ $user->is_active ? 'إيقاف' : 'تفعيل' }}">
                                            <i class="fas {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                        </button>
                                        @endcan
                                        @can('users.delete')
                                        <button wire:click="deleteUser({{ $user->id }})" 
                                                onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')"
                                                class="text-red-600 hover:text-red-900" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">لا يوجد مستخدمين بعد</p>
                <button wire:click="$set('showAddModal', true)" 
                        class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    إضافة أول مستخدم
                </button>
            </div>
        @endif
    </div>

    <!-- Modal إضافة مستخدم جديد -->
    @if($showAddModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">إضافة مستخدم جديد</h3>
                    
                    <form wire:submit.prevent="addUser">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">الاسم</label>
                            <input type="text" wire:model="name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">البريد الإلكتروني</label>
                            <input type="email" wire:model="email" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">كلمة المرور</label>
                            <input type="password" wire:model="password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">الدور</label>
                            <select wire:model="role" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">اختر الدور</option>
                                @foreach($roles as $roleOption)
                                    <option value="{{ $roleOption->name }}">{{ $roleOption->name }}</option>
                                @endforeach
                            </select>
                            @error('role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="is_active" 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="mr-2 text-sm text-gray-700">نشط</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-2 space-x-reverse">
                            <button type="button" wire:click="$set('showAddModal', false)" 
                                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                إلغاء
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                إضافة المستخدم
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal تعديل المستخدم -->
    @if($showEditModal && $editingUser)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">تعديل المستخدم</h3>
                    
                    <form wire:submit.prevent="updateUser">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">الاسم</label>
                            <input type="text" wire:model="edit_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('edit_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">البريد الإلكتروني</label>
                            <input type="email" wire:model="edit_email" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('edit_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">كلمة المرور الجديدة (اختياري)</label>
                            <input type="password" wire:model="edit_password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('edit_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">الدور</label>
                            <select wire:model="edit_role" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">اختر الدور</option>
                                @foreach($roles as $roleOption)
                                    <option value="{{ $roleOption->name }}">{{ $roleOption->name }}</option>
                                @endforeach
                            </select>
                            @error('edit_role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="edit_is_active" 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="mr-2 text-sm text-gray-700">نشط</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-2 space-x-reverse">
                            <button type="button" wire:click="$set('showEditModal', false)" 
                                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                إلغاء
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                تحديث المستخدم
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div> 