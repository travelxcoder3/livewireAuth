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
    @php
        use App\Tables\UserTable;
        $columns = UserTable::columns();
        // تجهيز الصفوف مع الحقول المخصصة
        $rows = $users->map(function($user) {
            $user->role_display = $user->roles->first()?->name ?? 'بدون دور';
            $user->status_display = $user->is_active ? 'نشط' : 'غير نشط';
            return $user;
        });
    @endphp
    <x-data-table :rows="$rows" :columns="$columns" />

    <!-- مودال الإضافة / التعديل -->
    @if($showAddModal || $showEditModal)
    <div class="fixed inset-0 z-50 bg-black/10 flex items-start justify-center pt-24 backdrop-blur-sm" wire:key="modal-{{ $editingEmployee ?? 'new' }}-{{ now() }}">
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
