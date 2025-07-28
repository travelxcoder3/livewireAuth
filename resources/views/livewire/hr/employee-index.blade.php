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
                    <button wire:click="createEmployee"
                        class="text-white font-bold px-4 py-2 rounded-xl border border-white shadow-md transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        + إضافة موظف
                    </button>
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
            <button wire:click="resetFilters"
                class="text-gray-800 font-bold px-4 py-2 rounded-xl border border-gray-800 shadow-md transition duration-300 text-sm"
                style="background: linear-gradient(to right, rgb(var(--primary-200)) 0%, rgb(var(--primary-300)) 100%);">
                إعادة تهيئة الفلاتر
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
                   <div class="grid md:grid-cols-2 gap-8">
                        <x-input-field
                            name="name"
                            label="الاسم"
                            placeholder="الاسم"
                            wireModel="name"
                            errorName="name"
                        />

                        <x-input-field
                            name="email"
                            label="البريد الإلكتروني"
                            type="email"
                            placeholder="البريد الإلكتروني"
                            wireModel="email"
                            errorName="email"
                        />
                    </div>

                    <!-- الصف الثاني -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- كلمة المرور -->
                         <x-input-field
                                name="password"
                                label="{{ $editingEmployee ? 'كلمة مرور جديدة' : 'كلمة المرور' }}"
                                type="password"
                                placeholder="{{ $editingEmployee ? 'كلمة المرور الجديدة (اختياري)' : 'كلمة المرور' }}"
                                wireModel="password"
                                errorName="password"
                            />

                            <x-input-field
                                name="password_confirmation"
                                label="تأكيد كلمة المرور"
                                type="password"
                                placeholder="{{ $editingEmployee ? 'تأكيد كلمة المرور الجديدة' : 'تأكيد كلمة المرور' }}"
                                wireModel="password_confirmation"
                                errorName="password_confirmation"
                            />
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
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- رقم الهاتف -->
                         <x-input-field
                            name="phone"
                            label="رقم الهاتف"
                            placeholder="رقم الهاتف"
                            wireModel="phone"
                            errorName="phone"
                        />

                        <x-input-field
                            name="user_name"
                            label="اسم المستخدم"
                            placeholder="اسم المستخدم"
                            wireModel="user_name"
                            errorName="user_name"
                        />
                    </div>
                    <!-- الأزرار -->
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="closeForm"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                            إلغاء
                        </button>
                        <button type="submit"
                            class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                            {{ $editingEmployee ? 'تحديث' : 'إضافة' }}
                        </button>
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
