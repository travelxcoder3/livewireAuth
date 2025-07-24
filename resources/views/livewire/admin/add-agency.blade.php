@php
    use App\Services\ThemeService;

    $themeName = ThemeService::getSystemTheme();
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp



<div>
    <div class="flex flex-col h-screen overflow-hidden">
    <!-- القسم العلوي الثابت -->
    <div class="flex-none p-0 bg-gray-50">
        <!-- نموذج إضافة الوكالة -->
        <div class="bg-white rounded-xl shadow-md p-4">
<h2 class="text-xl font-bold text-center mb-4 text-black">

    إضافة وكالة جديدة وتعيين أدمن للوكالة
</h2>


@if($successMessage)
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 2000)"
                x-show="show"
                x-transition
                class="mb-4 text-white px-4 py-2 rounded-md shadow text-sm text-center"
                style="background-color: rgb({{ $colors['primary-500'] }});">
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
                    $containerClass = 'relative mt-3';
                @endphp

                    <!-- الحقول -->
                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_name" class="{{ $fieldClass }}" placeholder="اسم الوكالة" />
                            <label class="{{ $labelClass }}">اسم الوكالة</label>
                            @error('agency_name')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <select wire:model.defer="parent_id" class="{{ $fieldClass }}">
                                <option value="">وكالة رئيسية (بدون أب)</option>
                                @foreach($mainAgencies as $mainAgency)
                                    <option value="{{ $mainAgency->id }}">{{ $mainAgency->name }}</option>
                                @endforeach
                            </select>
                            <label class="{{ $labelClass }}">الوكالة الرئيسية</label>
                            @error('parent_id') <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="email" wire:model.defer="agency_email" class="{{ $fieldClass }}" placeholder="البريد الإلكتروني للوكالة" />
                            <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                            @error('agency_email')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                        </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_phone" class="{{ $fieldClass }}" placeholder="رقم الهاتف" />
                            <label class="{{ $labelClass }}">رقم الهاتف</label>
                            @error('agency_phone')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="landline" class="{{ $fieldClass }}" placeholder="الهاتف الثابت" />
                            <label class="{{ $labelClass }}">الهاتف الثابت</label>
                        @error('landline')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <select wire:model.defer="currency" class="{{ $fieldClass }}">
                                <option value="">اختر العملة</option>
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="USD">دولار أمريكي (USD)</option>
                            <option value="EUR">يورو (EUR)</option>
                        </select>
                            <label class="{{ $labelClass }}">العملة</label>
                        @error('currency')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_address" class="{{ $fieldClass }}" placeholder="العنوان" />
                            <label class="{{ $labelClass }}">العنوان</label>
                        @error('agency_address')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="license_number" class="{{ $fieldClass }}" placeholder="رقم الرخصة" />
                            <label class="{{ $labelClass }}">رقم الرخصة</label>
                        @error('license_number')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="commercial_record" class="{{ $fieldClass }}" placeholder="السجل التجاري" />
                            <label class="{{ $labelClass }}">السجل التجاري</label>
                        @error('commercial_record')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="tax_number" class="{{ $fieldClass }}" placeholder="الرقم الضريبي" />
                            <label class="{{ $labelClass }}">الرقم الضريبي</label>
                        @error('tax_number')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model.defer="license_expiry_date" class="{{ $fieldClass }}" placeholder="تاريخ انتهاء الرخصة" />
                            <label class="{{ $labelClass }}">انتهاء الرخصة</label>
                        @error('license_expiry_date')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <select wire:model.defer="status" class="{{ $fieldClass }}">
                            <option value="active">نشطة</option>
                            <option value="inactive">غير نشطة</option>
                            <option value="suspended">موقوفة</option>
                        </select>
                            <label class="{{ $labelClass }}">حالة الوكالة</label>
                        @error('status')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="file" wire:model="logo" class="{{ $fieldClass }}" />

                            <label class="{{ $labelClass }}">شعار الوكالة</label>
                        @error('logo')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }} md:col-span-2">
                            <textarea wire:model.defer="description" rows="2" class="{{ $fieldClass }}" placeholder=" "></textarea>
                            <label class="{{ $labelClass }}">وصف الوكالة</label>
                        @error('description')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model.defer="subscription_start_date" class="{{ $fieldClass }}" placeholder="تاريخ بداية الاشتراك" />
                            <label class="{{ $labelClass }}">بداية الاشتراك</label>
                        @error('subscription_start_date')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model.defer="subscription_end_date" class="{{ $fieldClass }}" placeholder="تاريخ نهاية الاشتراك" />
                            <label class="{{ $labelClass }}">نهاية الاشتراك</label>
                        @error('subscription_end_date')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror


                        </div>
                                        <!-- ... الحقول الأخرى ... -->
                <div class="{{ $containerClass }}">
                    <input type="number" wire:model.defer="max_users" class="{{ $fieldClass }}" placeholder="الحد الأقصى للمستخدمين" min="1" max="100" />
                    <label class="{{ $labelClass }}">الحد الأقصى للمستخدمين</label>
                    @error('max_users')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                            </div>
                    </div>

                <!-- ... باقي الحقول ... -->
            <div class="border-t border-gray-200 pt-6 mt-6">
                <h3 class="text-lg font-bold mb-4 text-center text-black">
                    بيانات أدمن الوكالة
                </h3>

                <div class="flex flex-wrap md:flex-nowrap justify-between items-end gap-2">

                    <!-- الحقول الثلاثة -->
                    <div class="flex flex-wrap md:flex-nowrap gap-2 w-full md:w-[82%]">
                        <div class="{{ $containerClass }} w-full md:w-1/3">
                            <input type="text" wire:model.defer="admin_name" class="{{ $fieldClass }}" placeholder="اسم الأدمن" />
                            <label class="{{ $labelClass }}">اسم الأدمن</label>
                            @error('admin_name')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                        </div>

                        <div class="{{ $containerClass }} w-full md:w-1/3">
                            <input type="email" wire:model.defer="admin_email" class="{{ $fieldClass }}" placeholder="بريد الأدمن" />
                            <label class="{{ $labelClass }}">بريد الأدمن</label>
                            @error('admin_email')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                        </div>

                        <div class="flex gap-2 w-full">
    {{-- كلمة المرور --}}
    <div class="{{ $containerClass }} w-1/2" x-data="{ show: false }">
        <div class="relative">
            <input :type="show ? 'text' : 'password'" wire:model.defer="admin_password" class="{{ $fieldClass }} pr-10" placeholder="كلمة المرور" />
            <button type="button" @click="show = !show" class="absolute inset-y-0 left-2 flex items-center text-gray-500">
                <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg x-show="show" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.055 10.055 0 012.203-3.568M6.6 6.6l10.8 10.8M6.6 17.4L17.4 6.6"/>
                </svg>
            </button>
        </div>
        <label class="{{ $labelClass }}">كلمة المرور</label>
        @error('admin_password')
            <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">{{ $message }}</span>
        @enderror
    </div>

    {{-- تأكيد كلمة المرور --}}
    <div class="{{ $containerClass }} w-1/2" x-data="{ show: false }">
        <div class="relative">
            <input :type="show ? 'text' : 'password'" wire:model.defer="admin_password_confirmation" class="{{ $fieldClass }} pr-10" placeholder="تأكيد كلمة المرور" />
            <button type="button" @click="show = !show" class="absolute inset-y-0 left-2 flex items-center text-gray-500">
                <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg x-show="show" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.055 10.055 0 012.203-3.568M6.6 6.6l10.8 10.8M6.6 17.4L17.4 6.6"/>
                </svg>
            </button>
        </div>
        <label class="{{ $labelClass }}">تأكيد كلمة المرور</label>
            @error('admin_password_confirmation')
                <span class="absolute -bottom-3 right-0 text-red-600 text-[10px] leading-tight mt-0.5">{{ $message }}</span>
            @enderror
    </div>
</div>

                    </div>

                    <!-- الزر -->
                    <div class="w-full md:w-[18%] flex justify-end">
                        <x-primary-button type="submit" width="w-80">
                            حفظ الوكالة
                        </x-primary-button>
                    </div>
                </div>
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
