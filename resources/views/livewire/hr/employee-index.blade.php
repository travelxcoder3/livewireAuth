<div class="space-y-6">
    <!-- رسالة النجاح -->
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show" x-transition
            class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
            style="background-color: rgb(var(--primary-500));">
            {{ session('success') }}
        </div>
    @endif

    <!-- العنوان وزر الإضافة -->
    <div class="flex flex-col space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold"
                style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
                قائمة الموظفين
            </h2>

            <div class="flex space-x-4">
                @can('employees.create')
                    <!-- زر إضافة موظف -->
                        <x-primary-button wire:click="createEmployee" padding="px-4 py-2">
                             إضافة موظف
                        </x-primary-button>

                @endcan
            </div>
        </div>
    </div>

    <!-- البحث والفلاتر -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-3">
            <div class="relative mt-1">
                <input type="text" wire:model.live="search"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer"
                    placeholder=" ">
                <label
                    class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
                    ابحث بالاسم أو البريد
                </label>
            </div>

            <x-select-field label="القسم" :options="$departments" wireModel="department_filter" />

            <x-select-field label="الوظيفة" :options="$positions" wireModel="position_filter" />

            <div class="flex items-center text-xs" style="color: rgb(var(--primary-700));"
                wire:key="count-{{ $employees->total() }}">
                عدد الموظفين: {{ $employees->total() }}
            </div>
        </div>

        <!-- زر إعادة تهيئة الفلاتر -->
        <div class="flex justify-end">
           <button type="button" wire:click="resetFilters"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm w-full sm:w-auto">
            تنظيف الفلاتر
        </button>
        </div>
    </div>

    <!-- نافذة إضافة/تعديل الموظف -->
    @if ($showForm)
        <div class="fixed inset-0 z-50 bg-black/10 flex items-start justify-center pt-24 backdrop-blur-sm"
            wire:key="modal-{{ $editingEmployee ?? 'new' }}-{{ now() }}">
            <div
                class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
                <button wire:click="closeForm"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                    &times;
                </button>

                <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    {{ $editingEmployee ? 'تعديل الموظف' : 'إضافة موظف جديد' }}
                </h3>

                <form wire:submit.prevent="{{ $editingEmployee ? 'updateEmployee' : 'addEmployee' }}"
                    class="space-y-4 text-sm"
                    wire:key="employee-form-{{ $editingEmployee ?? 'new' }}-{{ now() }}">
                    @php
                        $fieldClass =
                            'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                        $labelClass =
                            'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                        $containerClass = 'relative mt-1';
                    @endphp

                    <!-- الصف الأول -->
                <!-- الصف الأول -->
<div class="grid md:grid-cols-2 gap-8">
      <div class="relative">
        <input type="text" id="name" name="name" wire:model.defer="name" placeholder=" "
            class="peer w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-transparent
            focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))]">
        <label for="name" class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all
            peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400
            peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-gray-500">
            الاسم
        </label>
        @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div class="relative">
        <input type="email" id="email" name="email" wire:model.defer="email" placeholder=" "
            class="peer w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-transparent
            focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))]">
        <label for="email" class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all
            peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400
            peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-gray-500">
            البريد الإلكتروني
        </label>
        @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>
</div>

                    <!-- الصف الثاني -->
                <div class="grid md:grid-cols-2 gap-6 mt-6">
     <div class="relative">
        <input type="password" id="password" name="password" wire:model.defer="password" placeholder=" "
            class="peer w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-transparent
            focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))]">
        <label for="password" class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all
            peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400
            peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-gray-500">
            {{ $editingEmployee ? 'كلمة المرور الجديدة (اختياري)' : 'كلمة المرور' }}
        </label>
        @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div class="relative">
        <input type="password" id="password_confirmation" name="password_confirmation" wire:model.defer="password_confirmation" placeholder=" "
            class="peer w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-transparent
            focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))]">
        <label for="password_confirmation" class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all
            peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400
            peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-gray-500">
            {{ $editingEmployee ? 'تأكيد كلمة المرور الجديدة' : 'تأكيد كلمة المرور' }}
        </label>
        @error('password_confirmation') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>
</div>

                    <!-- الصف الثالث -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- القسم -->
                        <x-select-field label="القسم" :options="$departments" wireModel="form_department_id"
                            errorName="form_department_id" containerClass="{{ $containerClass }}" />

                        <!-- الوظيفة -->
                        <x-select-field label="الوظيفة" :options="$positions" wireModel="form_position_id"
                            errorName="form_position_id" containerClass="{{ $containerClass }}" />
                    </div>

                    <!-- الصف الرابع -->
                 <!-- الصف الرابع -->
<div class="grid md:grid-cols-2 gap-6 mt-6">
     <div class="relative">
        <input type="text" id="phone" name="phone" wire:model.defer="phone" placeholder=" "
            class="peer w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-transparent
            focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))]">
        <label for="phone" class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all
            peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400
            peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-gray-500">
            رقم الهاتف
        </label>
        @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div class="relative">
        <input type="text" id="user_name" name="user_name" wire:model.defer="user_name" placeholder=" "
            class="peer w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-transparent
            focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))]">
        <label for="user_name" class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all
            peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400
            peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-gray-500">
            اسم المستخدم
        </label>
        @error('user_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>
</div>
                    <!-- الأزرار -->
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="closeForm"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                            إلغاء
                        </button>
                            <x-primary-button type="submit" padding="px-4 py-2">
                                {{ $editingEmployee ? 'تحديث' : 'إضافة' }}
                            </x-primary-button>

                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- جدول الموظفين -->
    @php
        use App\Tables\EmployeeTable;
        $columns = EmployeeTable::columns();
    @endphp
    <x-data-table :rows="$employees" :columns="$columns" />

    <style>
        .peer:placeholder-shown+label {
            top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .peer:not(:placeholder-shown)+label,
        .peer:focus+label {
            top: -0.5rem;
            font-size: 0.75rem;
            color: rgb(var(--primary-600));
        }

        select:required:invalid {
            color: #6b7280;
        }

        select option {
            color: #111827;
        }

        button[wire\:click="createEmployee"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        button[wire\:click="createEmployee"]:active {
            transform: translateY(0);
        }

        form button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        form button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>
</div>
