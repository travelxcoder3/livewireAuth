<div class="space-y-6">
    <!-- رسالة النجاح -->
    @if (session()->has('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 2000)"
             x-show="show"
             x-transition
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm" 
             style="background-color: rgb(var(--primary-500));">
            {{ session('success') }}
        </div>
    @endif

    <!-- العنوان وزر الإضافة -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            قائمة الموظفين
        </h2>
        @can('employees.create')
        <button wire:click="createEmployee" 
                class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
            + إضافة موظف
        </button>
        @endcan
    </div>

    <!-- البحث والفلاتر -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="grid md:grid-cols-4 gap-3">
            <div class="relative mt-1">
                <input type="text" 
                       wire:model.live="search"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer"
                       placeholder=" ">
                <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
                    ابحث بالاسم أو البريد
                </label>
            </div>

            <div class="relative mt-1">
                <select wire:model.lazy="department_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer">
                    <option value="">كل الأقسام</option>
                    @foreach($departments as $id => $name)
                        <option wire:key="dep-{{ $id }}" value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
                    القسم
                </label>
            </div>

            <div class="relative mt-1">
                <select wire:model.lazy="position_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer">
                    <option value="">كل الوظائف</option>
                    @foreach($positions as $id => $name)
                        <option wire:key="pos-{{ $id }}" value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
                    الوظيفة
                </label>
            </div>

            <div class="flex items-center text-xs" style="color: rgb(var(--primary-700));" wire:key="count-{{ $employees->total() }}">
                عدد الموظفين: {{ $employees->total() }}
            </div>
        </div>
    </div>

    <!-- نافذة إضافة/تعديل الموظف -->
    @if($showForm)
    <div class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center backdrop-blur-sm" wire:key="modal-{{ $editingEmployee ?? 'new' }}-{{ now() }}">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
            <button wire:click="closeForm"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                &times;
            </button>

            <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                {{ $editingEmployee ? 'تعديل الموظف' : 'إضافة موظف جديد' }}
            </h3>

            <form wire:submit.prevent="{{ $editingEmployee ? 'updateEmployee' : 'addEmployee' }}" class="space-y-4 text-sm" wire:key="employee-form-{{ $editingEmployee ?? 'new' }}-{{ now() }}">
                @php
                    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                    $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                    $containerClass = 'relative mt-1';
                @endphp

                <!-- الصف الأول -->
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- الاسم -->
                    <div class="{{ $containerClass }}">
                        <input wire:model="name" type="text" class="{{ $fieldClass }}" placeholder="الاسم" />
                        <label class="{{ $labelClass }}">الاسم</label>
                        @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- البريد الإلكتروني -->
                    <div class="{{ $containerClass }}">
                        <input wire:model="email" type="email" class="{{ $fieldClass }}" placeholder="البريد الإلكتروني" />
                        <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                        @error('email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- الصف الثاني -->
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- كلمة المرور -->
                    <div class="{{ $containerClass }}">
                        <input wire:model="password" type="password" class="{{ $fieldClass }}" 
                               placeholder="{{ $editingEmployee ? 'كلمة المرور الجديدة (اختياري)' : 'كلمة المرور' }}" />
                        <label class="{{ $labelClass }}">{{ $editingEmployee ? 'كلمة مرور جديدة' : 'كلمة المرور' }}</label>
                        @error('password') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- تأكيد كلمة المرور -->
                    <div class="{{ $containerClass }}">
                        <input wire:model="password_confirmation" type="password" class="{{ $fieldClass }}" 
                               placeholder="{{ $editingEmployee ? 'تأكيد كلمة المرور الجديدة' : 'تأكيد كلمة المرور' }}" />
                        <label class="{{ $labelClass }}">تأكيد كلمة المرور</label>
                    </div>
                </div>

                <!-- الصف الثالث -->
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- القسم -->
                    <div class="{{ $containerClass }}">
                        <select wire:model="department_id" class="{{ $fieldClass }}">
                            <option value="">اختر القسم</option>
                            @foreach($departments as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <label class="{{ $labelClass }}">القسم</label>
                        @error('department_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- الوظيفة -->
                    <div class="{{ $containerClass }}">
                        <select wire:model="position_id" class="{{ $fieldClass }}">
                            <option value="">اختر الوظيفة</option>
                            @foreach($positions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <label class="{{ $labelClass }}">الوظيفة</label>
                        @error('position_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- الصف الرابع -->
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- رقم الهاتف -->
                    <div class="{{ $containerClass }}">
                        <input wire:model="phone" type="text" class="{{ $fieldClass }}" placeholder="رقم الهاتف" />
                        <label class="{{ $labelClass }}">رقم الهاتف</label>
                        @error('phone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- الفرع -->
                    <div class="{{ $containerClass }}">
                        <input wire:model="branch" type="text" class="{{ $fieldClass }}" placeholder="الفرع" />
                        <label class="{{ $labelClass }}">الفرع</label>
                        @error('branch') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- اسم المستخدم -->
                <div class="{{ $containerClass }}">
                    <input wire:model="user_name" type="text" class="{{ $fieldClass }}" placeholder="اسم المستخدم" />
                    <label class="{{ $labelClass }}">اسم المستخدم</label>
                    @error('user_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
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
    <div class="bg-white rounded-xl shadow-md overflow-hidden" wire:key="employees-table-{{ $employees->count() }}-{{ now() }}">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-2 py-1">#</th>
                        <th class="px-2 py-1">الاسم</th>
                        <th class="px-2 py-1">اسم المستخدم</th>
                        <th class="px-2 py-1">البريد الإلكتروني</th>
                        <th class="px-2 py-1">الهاتف</th>
                        <th class="px-2 py-1">الفرع</th>
                        <th class="px-2 py-1">القسم</th>
                        <th class="px-2 py-1">الوظيفة</th>
                        <th class="px-2 py-1">تاريخ الإنشاء</th>
                        <th class="px-2 py-1">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-gray-50" wire:key="employee-{{ $employee->id }}">
                            <td class="px-2 py-1 whitespace-nowrap">{{ $loop->iteration }}</td>
                            <td class="px-2 py-1">{{ $employee->name }}</td>
                            <td class="px-2 py-1">{{ $employee->user_name ?? '—' }}</td>
                            <td class="px-2 py-1">{{ $employee->email }}</td>
                            <td class="px-2 py-1">{{ $employee->phone ?? '—' }}</td>
                            <td class="px-2 py-1">{{ $employee->branch ?? '—' }}</td>
                            <td class="px-2 py-1">{{ $employee->department?->name ?? '—' }}</td>
                            <td class="px-2 py-1">{{ $employee->position?->name ?? '—' }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $employee->created_at->format('Y-m-d') }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <div class="flex gap-2">
                                    @can('employees.edit')
                                    <button wire:click="editEmployee({{ $employee->id }})"
                                            class="font-medium text-xs" style="color: rgb(var(--primary-600));">
                                        تعديل
                                    </button>
                                    @endcan
                                  
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-gray-400">
                                لا توجد نتائج مطابقة لبحثك.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($employees->hasPages())
            <div class="px-4 py-2 border-t border-gray-200" wire:key="pagination-{{ $employees->currentPage() }}">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

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
        
        select:required:invalid {
            color: #6b7280;
        }
        
        select option {
            color: #111827;
        }

        /* تأثير hover لزر الإضافة */
        button[wire\:click="createEmployee"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        button[wire\:click="createEmployee"]:active {
            transform: translateY(0);
        }
        
        /* تأثير زر الحفظ في النافذة المنبثقة */
        form button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        form button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>
</div>