@php
    use App\Services\ThemeService;

    $themeName = ThemeService::getSystemTheme();
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp



<div>
    <div class="flex flex-col h-screen overflow-hidden">
    <!-- القسم العلوي الثابت -->
    <div class="flex-none p-4 bg-gray-50">
        <!-- نموذج إضافة الوكالة -->
        <div class="bg-white rounded-xl shadow-md p-4">
<h2 class="text-xl font-bold text-center mb-4 text-black">

    إضافة وكالة جديدة وتعيين أدمن للوكالة
</h2>


            @if($successMessage)
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg text-center">
                    {{ $successMessage }}
                </div>
            @endif
            @error('general')
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg text-center">
                    {{ $message }}
                </div>
            @enderror

            <form wire:submit.prevent="save" class="space-y-4 text-sm" id="mainForm">
                @php
                        $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none bg-white text-xs peer';
                        $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs';
                    $containerClass = 'relative mt-1';
                @endphp

                    <!-- الحقول -->
                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_name" class="{{ $fieldClass }}" placeholder="اسم الوكالة" />
                            <label class="{{ $labelClass }}">اسم الوكالة</label>
                            @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="main_branch_name" class="{{ $fieldClass }}" placeholder="اسم الفرع الرئيسي" />
                            <label class="{{ $labelClass }}">اسم الفرع الرئيسي</label>
                        @error('main_branch_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="email" wire:model.defer="agency_email" class="{{ $fieldClass }}" placeholder="البريد الإلكتروني للوكالة" />
                            <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                            @error('email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_phone" class="{{ $fieldClass }}" placeholder="رقم الهاتف" />
                            <label class="{{ $labelClass }}">رقم الهاتف</label>
                            @error('phone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="landline" class="{{ $fieldClass }}" placeholder="الهاتف الثابت" />
                            <label class="{{ $labelClass }}">الهاتف الثابت</label>
                        @error('landline') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <select wire:model.defer="currency" class="{{ $fieldClass }}">
                                <option value="">اختر العملة</option>
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="USD">دولار أمريكي (USD)</option>
                            <option value="EUR">يورو (EUR)</option>
                        </select>
                            <label class="{{ $labelClass }}">العملة</label>
                        @error('currency') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_address" class="{{ $fieldClass }}" placeholder="العنوان" />
                            <label class="{{ $labelClass }}">العنوان</label>
                        @error('agency_address') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="license_number" class="{{ $fieldClass }}" placeholder="رقم الرخصة" />
                            <label class="{{ $labelClass }}">رقم الرخصة</label>
                        @error('license_number') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="commercial_record" class="{{ $fieldClass }}" placeholder="السجل التجاري" />
                            <label class="{{ $labelClass }}">السجل التجاري</label>
                        @error('commercial_record') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="tax_number" class="{{ $fieldClass }}" placeholder="الرقم الضريبي" />
                            <label class="{{ $labelClass }}">الرقم الضريبي</label>
                        @error('tax_number') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model.defer="license_expiry_date" class="{{ $fieldClass }}" placeholder="تاريخ انتهاء الرخصة" />
                            <label class="{{ $labelClass }}">انتهاء الرخصة</label>
                        @error('license_expiry_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <select wire:model.defer="status" class="{{ $fieldClass }}">
                            <option value="active">نشطة</option>
                            <option value="inactive">غير نشطة</option>
                            <option value="suspended">موقوفة</option>
                        </select>
                            <label class="{{ $labelClass }}">حالة الوكالة</label>
                        @error('status') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="file" wire:model="logo" class="{{ $fieldClass }}" />

                            <label class="{{ $labelClass }}">شعار الوكالة</label>
                        @error('logo') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }} md:col-span-2">
                            <textarea wire:model.defer="description" rows="2" class="{{ $fieldClass }}" placeholder=" "></textarea>
                            <label class="{{ $labelClass }}">وصف الوكالة</label>
                        @error('description') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model.defer="subscription_start_date" class="{{ $fieldClass }}" placeholder="تاريخ بداية الاشتراك" />
                            <label class="{{ $labelClass }}">بداية الاشتراك</label>
                        @error('subscription_start_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model.defer="subscription_end_date" class="{{ $fieldClass }}" placeholder="تاريخ نهاية الاشتراك" />
                            <label class="{{ $labelClass }}">نهاية الاشتراك</label>
                        @error('subscription_end_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
    <!-- ... الحقول الأخرى ... -->
    <div class="{{ $containerClass }}">
        <input type="number" wire:model.defer="max_users" class="{{ $fieldClass }}" placeholder="الحد الأقصى للمستخدمين" min="1" max="100" />
        <label class="{{ $labelClass }}">الحد الأقصى للمستخدمين</label>
        @error('max_users') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>
    <!-- ... باقي الحقول ... -->
                    <!-- بيانات الأدمن -->
                <div class="border-t border-gray-200 pt-6 mt-6">
                     <h3 class="text-lg font-bold mb-4 text-center text-black">

                            بيانات أدمن الوكالة
                        </h3>

                    <div class="grid md:grid-cols-3 gap-3">
                            <div class="{{ $containerClass }}">
                                <input type="text" wire:model.defer="admin_name" class="{{ $fieldClass }}" placeholder="اسم الأدمن" />
                                <label class="{{ $labelClass }}">اسم الأدمن</label>
                            @error('admin_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                            <div class="{{ $containerClass }}">
                                <input type="email" wire:model.defer="admin_email" class="{{ $fieldClass }}" placeholder="بريد الأدمن" />
                                <label class="{{ $labelClass }}">بريد الأدمن</label>
                            @error('admin_email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                            <div class="{{ $containerClass }}">
                                <input type="password" wire:model.defer="admin_password" class="{{ $fieldClass }}" placeholder="كلمة المرور" />
                                <label class="{{ $labelClass }}">كلمة المرور</label>
                            @error('admin_password') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                    </div>
                </div>

                    <!-- زر الحفظ -->
                <div class="mt-8 flex justify-center">
                    <button type="submit" 
                            class="text-white font-bold px-8 py-3 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm"
                            style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}) 0%, rgb({{ $colors['primary-600'] }}) 100%);">
                        حفظ الوكالة
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <style>
        input, select, textarea {
            text-align: center;
        }
        input:focus::placeholder, select:focus::placeholder, textarea:focus::placeholder {
            color: transparent;
        }
        input:focus, select:focus, textarea:focus {
            border-color: rgb({{ $colors['primary-500'] }}) !important;
            box-shadow: 0 0 0 2px rgba({{ $colors['primary-500'] }}, 0.2) !important;
        }
        .peer:placeholder-shown + label {
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.95rem;
            color: #6b7280;
            right: 1.5rem;
            left: 1.5rem;
            text-align: center;
            width: auto;
            pointer-events: none;
        }
        .peer:not(:placeholder-shown) + label,
        .peer:focus + label {
            top: -0.5rem;
            right: 0.75rem;
            left: auto;
            font-size: 0.75rem;
            color: rgb({{ $colors['primary-500'] }}) !important;
            background: #fff;
            padding: 0 0.25rem;
            text-align: right;
            width: auto;
        }
        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba({{ $colors['primary-500'] }}, 0.2);
        }
        button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>
</div>
