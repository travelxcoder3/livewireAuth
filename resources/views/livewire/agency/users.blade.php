<div class="space-y-6">
    <!-- العنوان الرئيسي -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة المستخدمين
        </h2>
        
    </div>
<!-- البحث والفلاتر -->
<div class="bg-white rounded-xl shadow-md p-4 mb-6">
    <div class="grid md:grid-cols-4 gap-3">
        <!-- حقل البحث -->
        <div class="relative mt-1">
            <input type="text" wire:model.live="search"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer"
                placeholder=" ">
            <label
                class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
                ابحث بالاسم أو البريد
            </label>
        </div>

        <!-- فلتر الدور -->
        <div class="relative mt-1">
            <select wire:model.live="role_filter"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer">
                <option value="">كل الأدوار</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
            <label
                class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
                الدور
            </label>
        </div>

        <!-- فلتر الحالة -->
        <div class="relative mt-1">
            <select wire:model.live="status_filter"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer">
                <option value="">كل الحالات</option>
                <option value="1">نشط</option>
                <option value="0">غير نشط</option>
            </select>
            <label
                class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
                الحالة
            </label>
        </div>

        <!-- عدد المستخدمين -->
        <div class="flex items-center text-xs" style="color: rgb(var(--primary-700));">
            عدد المستخدمين: {{ $users->count() }}
        </div>
    </div>

    <!-- زر إعادة تعيين الفلاتر -->
    <div class="flex justify-end mt-3">
        <button wire:click="resetFilters"
            class="text-gray-800 font-bold px-4 py-2 rounded-xl border border-gray-800 shadow-md transition duration-300 text-sm"
            style="background: linear-gradient(to right, rgb(var(--primary-200)) 0%, rgb(var(--primary-300)) 100%);">
            إعادة تعيين الفلاتر
        </button>
    </div>
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
    <div class="fixed inset-0 z-50 bg-black/10 flex items-start justify-center pt-10 backdrop-blur-sm" wire:key="modal-{{ $editingEmployee ?? 'new' }}-{{ now() }}">        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
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
                @if($showEditModal && $editingUser && $editingUser->roles->first()?->name === 'agency-admin')
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">الدور</label>
                        <input type="text" value="agency-admin" readonly class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-gray-100 text-xs cursor-not-allowed" />
                    </div>
                @else
                    <x-select-field
                        wireModel="{{ $showEditModal ? 'edit_role' : 'role' }}"
                        name="role"
                        label="الدور"
                        placeholder="اختر دور"
                        :options="$roles->pluck('name', 'name')->toArray()"
                        errorName="{{ $showEditModal ? 'edit_role' : 'role' }}"
                    />
                @endif

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
        <x-checkbox-field
            name="is_active"
            label="نشط"
            wireModel="{{ $showEditModal ? 'edit_is_active' : 'is_active' }}"
            :checked="false"
            containerClass="mt-4"
            fieldClass=""
            labelClass="text-xs text-gray-700"
        />

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