<div class="space-y-6">
    <!-- العنوان والزر الرجوع -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إضافة موظف جديد
        </h2>
        <a href="{{ route('agency.hr.employees.index') }}"
           class="text-white font-bold py-2 px-4 rounded-xl shadow-md transition duration-300 text-sm"
           style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
            ← العودة للقائمة
        </a>
    </div>

    <!-- نموذج الإضافة -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <form wire:submit.prevent="save" class="space-y-4 text-sm">
            @php
                $fieldClass = 'w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-sm peer';
                $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-sm text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-[rgb(var(--primary-600))]';
                $containerClass = 'relative mt-1';
            @endphp

            <!-- الصف الأول -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- الاسم -->
                <div class="{{ $containerClass }}">
                    <input type="text" wire:model.defer="name" class="{{ $fieldClass }}" placeholder=" " />
                    <label class="{{ $labelClass }}">الاسم</label>
                    @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- البريد الإلكتروني -->
                <div class="{{ $containerClass }}">
                    <input type="email" wire:model.defer="email" class="{{ $fieldClass }}" placeholder=" " />
                    <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                    @error('email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- الصف الثاني -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- كلمة المرور -->
                <div class="{{ $containerClass }}">
                    <input type="password" wire:model.defer="password" class="{{ $fieldClass }}" placeholder=" " />
                    <label class="{{ $labelClass }}">كلمة المرور</label>
                    @error('password') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- تأكيد كلمة المرور -->
                <div class="{{ $containerClass }}">
                    <input type="password" wire:model.defer="password_confirmation" class="{{ $fieldClass }}" placeholder=" " />
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
                    <input type="text" wire:model.defer="phone" class="{{ $fieldClass }}" placeholder=" " />
                    <label class="{{ $labelClass }}">رقم الهاتف</label>
                    @error('phone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- الفرع -->
                <div class="{{ $containerClass }}">
                    <input type="text" wire:model.defer="branch" class="{{ $fieldClass }}" placeholder=" " />
                    <label class="{{ $labelClass }}">الفرع</label>
                    @error('branch') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- اسم المستخدم -->
            <div class="{{ $containerClass }}">
                <input type="text" wire:model.defer="user_name" class="{{ $fieldClass }}" placeholder=" " />
                <label class="{{ $labelClass }}">اسم المستخدم</label>
                @error('user_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- زر الحفظ -->
            <div class="pt-4">
                <button type="submit"
                        class="w-full text-white font-bold py-3 px-6 rounded-xl shadow-md transition duration-300 text-base"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                    حفظ الموظف
                </button>
            </div>
        </form>
    </div>

    <style>
        .peer:placeholder-shown + label {
            top: 1rem;
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

        /* تأثيرات الأزرار */
        a[href="{{ route('agency.hr.employees.index') }}"]:hover,
        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        a[href="{{ route('agency.hr.employees.index') }}"]:active,
        button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>
</div>
