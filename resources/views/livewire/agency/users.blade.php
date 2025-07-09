<div class="space-y-6">
    <!-- العنوان الرئيسي -->
        <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة المستخدمين
        </h2>

            @can('users.create')
            <button wire:click="$set('showAddModal', true)" 
                class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
            + إضافة مستخدم جديد
            </button>
            @endcan
    </div>

    <!-- جدول المستخدمين -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-2 py-1">الاسم</th>
                    <th class="px-2 py-1">البريد الإلكتروني</th>
                    <th class="px-2 py-1">الدور</th>
                    <th class="px-2 py-1">الحالة</th>
                    <th class="px-2 py-1">الإجراءات</th>
                        </tr>
                    </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 py-1 font-medium">{{ $user->name }}</td>
                        <td class="px-2 py-1">{{ $user->email }}</td>
                        <td class="px-2 py-1">
                            @if($user->roles->first())
                                <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded-full">
                                    {{ $user->roles->first()->name }}
                                        </span>
                                    @else
                                <span class="text-xs text-gray-400">بدون دور</span>
                                    @endif
                                </td>
                        <td class="px-2 py-1">
                            <button wire:click="toggleUserStatus({{ $user->id }})"
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    style="{{ $user->is_active 
                                        ? 'background-color: rgba(var(--primary-500), 0.1); color: rgb(var(--primary-500));' 
                                        : 'background-color: rgba(239, 68, 68, 0.1); color: rgb(239, 68, 68);' }}">
                                {{ $user->is_active ? 'نشط' : 'غير نشط' }}
                            </button>
                        </td>
                        <td class="px-2 py-1">
                            <div class="flex gap-2">
                                        @can('users.edit')
                                        <button wire:click="editUser({{ $user->id }})" 
                                        class="text-xs font-medium" style="color: rgb(var(--primary-600));">
                                    تعديل
                                        </button>
                                        @endcan
                                        @can('users.delete')
                                        <button wire:click="deleteUser({{ $user->id }})" 
                                                onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')"
                                        class="text-xs font-medium text-red-600 hover:text-red-800">
                                    حذف
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-gray-400">
                            لا يوجد مستخدمين حالياً
                        </td>
                    </tr>
                @endforelse
                    </tbody>
                </table>
            </div>

    <!-- مودال الإضافة / التعديل -->
    @if($showAddModal || $showEditModal)
        <div class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
                <button wire:click="closeModal"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                    &times;
                </button>

                <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    {{ $showEditModal ? 'تعديل المستخدم' : 'إضافة مستخدم جديد' }}
                </h3>

                <form wire:submit.prevent="{{ $showEditModal ? 'updateUser' : 'addUser' }}" class="space-y-4 text-sm">
                    @php
                        $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                        $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                        $containerClass = 'relative mt-1';
                    @endphp

                    <!-- الاسم -->
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="{{ $showEditModal ? 'edit_name' : 'name' }}" class="{{ $fieldClass }}" placeholder="الاسم" />
                        <label class="{{ $labelClass }}">الاسم</label>
                        @error($showEditModal ? 'edit_name' : 'name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                    <!-- البريد -->
                    <div class="{{ $containerClass }}">
                        <input type="email" wire:model.defer="{{ $showEditModal ? 'edit_email' : 'email' }}" class="{{ $fieldClass }}" placeholder="البريد الإلكتروني" />
                        <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                        @error($showEditModal ? 'edit_email' : 'email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                    <!-- كلمة المرور -->
                    <div class="{{ $containerClass }}">
                        <input type="password" wire:model.defer="{{ $showEditModal ? 'edit_password' : 'password' }}" 
                               class="{{ $fieldClass }}" placeholder="{{ $showEditModal ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور' }}" />
                        <label class="{{ $labelClass }}">{{ $showEditModal ? 'كلمة مرور جديدة' : 'كلمة المرور' }}</label>
                        @error($showEditModal ? 'edit_password' : 'password') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                    <!-- الدور -->
                    <div class="{{ $containerClass }}">
                        <select wire:model.defer="{{ $showEditModal ? 'edit_role' : 'role' }}" class="{{ $fieldClass }}">
                            <option value="">-- اختر دور --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                        <label class="{{ $labelClass }}">الدور</label>
                        @error($showEditModal ? 'edit_role' : 'role') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                    <!-- الحالة -->
                    <div class="flex items-center mt-4">
                        <input type="checkbox" wire:model.defer="{{ $showEditModal ? 'edit_is_active' : 'is_active' }}"
                               class="h-4 w-4 text-green-600 border-gray-300 rounded">
                        <label class="mr-2 text-xs text-gray-700">نشط</label>
                </div>

                    <!-- الأزرار -->
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="closeModal"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                        إلغاء
                    </button>
                    <button type="submit"
                                class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                            {{ $showEditModal ? 'تحديث' : 'إضافة' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

    <!-- رسالة نجاح -->
    @if(session()->has('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 2000)"
             x-show="show"
             x-transition
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
             style="background-color: rgb(var(--primary-500));">
            {{ session('success') }}
    </div>
@endif

    <!-- CSS مخصص -->
    <style>
        .peer:placeholder-shown + label {
            top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .peer:not(:placeholder-shown) + label,
        .peer:focus + label {
            top: -0.5rem;
            font-size: 0.75rem;
            color: rgb(var(--primary-600));
        }
    </style>
</div> 
