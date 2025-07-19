<div class="space-y-6">
    <!-- العنوان الرئيسي -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة المستخدمين
        </h2>
    </div>

    <!-- جدول المستخدمين -->
    @php
        use App\Tables\UserTable;
        $columns = UserTable::columns();
        // تجهيز الصفوف مع الحقول المخصصة
        $rows = $users->map(function($user) {
            $user->role_display = $user->roles->first()?->name ?? 'بدون دور';
            $user->status_display = $user->is_active ? 'نشط' : 'غير نشط';
            $user->agency_name = $user->agency->name ?? '-';
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
                <!-- الاسم -->
                <x-input-field 
                    wireModel="{{ $showEditModal ? 'edit_name' : 'name' }}"
                    name="name"
                    label="الاسم"
                    placeholder="الاسم"
                    errorName="{{ $showEditModal ? 'edit_name' : 'name' }}"
                />

                <!-- البريد -->
                <x-input-field 
                    type="email"
                    wireModel="{{ $showEditModal ? 'edit_email' : 'email' }}"
                    name="email"
                    label="البريد الإلكتروني"
                    placeholder="البريد الإلكتروني"
                    errorName="{{ $showEditModal ? 'edit_email' : 'email' }}"
                />

                <!-- كلمة المرور -->
                <x-input-field 
                    type="password"
                    wireModel="{{ $showEditModal ? 'edit_password' : 'password' }}"
                    name="password"
                    label="{{ $showEditModal ? 'كلمة مرور جديدة' : 'كلمة المرور' }}"
                    placeholder="{{ $showEditModal ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور' }}"
                    errorName="{{ $showEditModal ? 'edit_password' : 'password' }}"
                />

                <!-- الدور -->
                <x-select-field
                    wireModel="{{ $showEditModal ? 'edit_role' : 'role' }}"
                    name="role"
                    label="الدور"
                    placeholder="اختر دور"
                    :options="$roles->pluck('name', 'name')->toArray()"
                    errorName="{{ $showEditModal ? 'edit_role' : 'role' }}"
                />

                <!-- الهدف المبيعي -->
                <x-input-field 
                    type="number"
                    wireModel="sales_target"
                    name="sales_target"
                    label="الهدف المبيعي"
                    placeholder="أدخل الهدف المبيعي"
                    errorName="sales_target"
                />

                <!-- الهدف الأساسي -->
                <x-input-field 
                    type="number"
                    wireModel="main_target"
                    name="main_target"
                    label="الهدف الأساسي"
                    placeholder="أدخل الهدف الأساسي"
                    errorName="main_target"
                />

                <!-- الحالة -->
                <div class="flex items-center mt-4">
                    <input type="checkbox" wire:model.defer="{{ $showEditModal ? 'edit_is_active' : 'is_active' }}"
                           class="h-4 w-4 text-green-600 border-gray-300 rounded">
                    <label class="mr-2 text-xs text-gray-700">نشط</label>
                </div>

                <!-- الأزرار -->
                <div class="flex justify-end gap-3 pt-4">
                    <x-primary-button 
                        type="button" 
                        wire:click="closeModal"
                        color="gray-200"
                        textColor="gray-800"
                        :gradient="false"
                        class="hover:bg-gray-300">
                        إلغاء
                    </x-primary-button>
                    
                    <x-primary-button type="submit">
                        {{ $showEditModal ? 'تحديث' : 'إضافة' }}
                    </x-primary-button>
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
</div>