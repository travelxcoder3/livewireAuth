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
<div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-bold text-black text-center w-full">
        إضافة وكالة جديدة وتعيين أدمن للوكالة
    </h2>
    <span class="text-sm font-normal text-gray-600">* <span class="text-red-500">الحقول المطلوبة</span></span>
</div>


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
                            <input type="text" wire:model.defer="agency_name" class="{{ $fieldClass }}" placeholder="اسم الوكالة *" />
                            <label class="{{ $labelClass }}">اسم الوكالة <span class="text-red-500">*</span></label>
                            @error('agency_name')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <div class="flex gap-2 mb-2 items-center">
                                <button type="button" 
                                    wire:click="$set('isMainAgency', true)" 
                                    class="agency-type-btn-theme {{ $isMainAgency ? 'active' : '' }}">
                                    وكالة رئيسية
                                </button>
                                <button type="button" 
                                    wire:click="$set('isMainAgency', false)" 
                                    class="agency-type-btn-theme {{ !$isMainAgency ? 'active' : '' }}">
                                    فرع تابع لوكالة رئيسية
                                </button>
                                @error('parent_id') <span class="inline-error">{{ $message }}</span> @enderror
                            </div>
                            @if(!$isMainAgency)
                                <select wire:model.defer="parent_id" class="{{ $fieldClass }}">
                                    <option value="">اختر الوكالة الرئيسية *</option>
                                    @foreach($mainAgencies as $mainAgency)
                                        <option value="{{ $mainAgency->id }}">{{ $mainAgency->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <label class="{{ $labelClass }}">الوكالة الرئيسية <span class="text-red-500">*</span></label>
                        </div>
                        <div class="{{ $containerClass }}">
                            <input type="email" wire:model.defer="agency_email" class="{{ $fieldClass }}" placeholder="البريد الإلكتروني للوكالة *" />
                            <label class="{{ $labelClass }}">البريد الإلكتروني <span class="text-red-500">*</span></label>
                            @error('agency_email')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                        </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_phone" class="{{ $fieldClass }}" placeholder="رقم الهاتف *" />
                            <label class="{{ $labelClass }}">رقم الهاتف <span class="text-red-500">*</span></label>
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
                                <option value="">اختر العملة *</option>
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="USD">دولار أمريكي (USD)</option>
                            <option value="EUR">يورو (EUR)</option>
                        </select>
                            <label class="{{ $labelClass }}">العملة <span class="text-red-500">*</span></label>
                        @error('currency')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_address" class="{{ $fieldClass }}" placeholder="العنوان *" />
                            <label class="{{ $labelClass }}">العنوان <span class="text-red-500">*</span></label>
                        @error('agency_address')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="license_number" class="{{ $fieldClass }}" placeholder="رقم الرخصة *" />
                            <label class="{{ $labelClass }}">رقم الرخصة <span class="text-red-500">*</span></label>
                        @error('license_number')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="commercial_record" class="{{ $fieldClass }}" placeholder="السجل التجاري *" />
                            <label class="{{ $labelClass }}">السجل التجاري <span class="text-red-500">*</span></label>
                        @error('commercial_record')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="tax_number" class="{{ $fieldClass }}" placeholder="الرقم الضريبي *" />
                            <label class="{{ $labelClass }}">الرقم الضريبي <span class="text-red-500">*</span></label>
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
                            <option value="">حالة الوكالة *</option>
                            <option value="active">نشطة</option>
                            <option value="inactive">غير نشطة</option>
                            <option value="suspended">موقوفة</option>
                        </select>
                            <label class="{{ $labelClass }}">حالة الوكالة <span class="text-red-500">*</span></label>
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
                            <textarea wire:model.defer="description" rows="2" class="{{ $fieldClass }}" placeholder="وصف الوكالة"></textarea>
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
                    <input type="number" wire:model.defer="max_users" class="{{ $fieldClass }}" placeholder="الحد الأقصى للمستخدمين *" min="1" max="100" />
                    <label class="{{ $labelClass }}">الحد الأقصى للمستخدمين <span class="text-red-500">*</span></label>
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

                <div class="flex flex-wrap md:flex-nowrap justify-between items-end gap-4">
                    <!-- الحقول الأربعة في صف واحد -->
                    <div class="flex flex-wrap md:flex-nowrap gap-2 w-full md:w-[70%]">
                        <div class="{{ $containerClass }} w-full md:w-1/4">
                            <input type="text" wire:model.defer="admin_name" class="{{ $fieldClass }}" placeholder="اسم الأدمن *" />
                            <label class="{{ $labelClass }}">اسم الأدمن <span class="text-red-500">*</span></label>
                            @error('admin_name')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                        </div>

                        <div class="{{ $containerClass }} w-full md:w-1/4">
                            <input type="email" wire:model.defer="admin_email" class="{{ $fieldClass }}" placeholder="بريد الأدمن *" />
                            <label class="{{ $labelClass }}">بريد الأدمن <span class="text-red-500">*</span></label>
                            @error('admin_email')     <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">
        {{ $message }}
    </span>
@enderror

                        </div>

                        <div class="{{ $containerClass }} w-full md:w-1/4">
                            <div class="relative" x-data="{ show: false }">
                                <input :type="show ? 'text' : 'password'" wire:model.defer="admin_password" class="{{ $fieldClass }} pr-10" placeholder="كلمة المرور *" />
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
                            <label class="{{ $labelClass }}">كلمة المرور <span class="text-red-500">*</span></label>
                            @error('admin_password')
                                <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="{{ $containerClass }} w-full md:w-1/4">
                            <div class="relative" x-data="{ show: false }">
                                <input :type="show ? 'text' : 'password'" wire:model.defer="admin_password_confirmation" class="{{ $fieldClass }} pr-10" placeholder="تأكيد كلمة المرور *" />
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
                            <label class="{{ $labelClass }}">تأكيد كلمة المرور <span class="text-red-500">*</span></label>
                            @error('admin_password_confirmation')
                                <span class="absolute -bottom-4 right-0 text-red-600 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- زر حفظ الوكالة في نفس الصف -->
                    <div class="w-full md:w-[30%]">
                        <button type="submit" 
                            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%); color: #fff;"
                            class="w-full px-6 py-3 rounded-lg font-medium text-sm transition duration-200 shadow hover:shadow-md">
                            حفظ الوكالة
                        </button>
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
        /* إخفاء نص الـ placeholder عند الضغط أو الكتابة داخل الحقل */
        input:focus::placeholder,
        input:not(:placeholder-shown)::placeholder,
        textarea:focus::placeholder,
        textarea:not(:placeholder-shown)::placeholder,
        select:focus::placeholder {
            color: transparent !important;
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

        /* ضمان ثبات تنسيق الحقول */
        .relative {
            position: relative;
            min-height: 60px; /* ارتفاع ثابت للحقول */
        }
        
        /* تنسيق ثابت للـ input fields */
        .w-full.rounded-lg.border.border-gray-300.px-3.py-2.focus\:outline-none.bg-white.text-xs.peer {
            height: 44px !important;
            line-height: 1.2;
            text-align: center;
            vertical-align: middle;
            font-size: 12px !important;
            padding: 12px 16px !important;
            border-radius: 8px !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
        }
        
        /* تنسيق ثابت للـ select */
        select.w-full.rounded-lg.border.border-gray-300.px-3.py-2.focus\:outline-none.bg-white.text-xs.peer {
            height: 44px !important;
            line-height: 1.2;
            text-align: center;
            vertical-align: middle;
            font-size: 12px !important;
            padding: 12px 16px !important;
            border-radius: 8px !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
        }
        
        /* تنسيق ثابت للـ buttons */
        .flex.gap-2.mb-2 button {
            height: 44px !important;
            font-size: 12px !important;
            padding: 12px 16px !important;
            border-radius: 8px !important;
            border: 1px solid #d1d5db !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* تنسيق ثابت للـ file input */
        input[type="file"] {
            height: 44px !important;
            font-size: 12px !important;
            padding: 12px 16px !important;
            border-radius: 8px !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
        }
        
        /* تنسيق ثابت للـ date input */
        input[type="date"] {
            height: 44px !important;
            font-size: 12px !important;
            padding: 12px 16px !important;
            border-radius: 8px !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
            text-align: center;
        }
        
        /* تنسيق ثابت للـ number input */
        input[type="number"] {
            height: 44px !important;
            font-size: 12px !important;
            padding: 12px 16px !important;
            border-radius: 8px !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
            text-align: center;
        }
        
        /* تنسيق ثابت للـ password input */
        input[type="password"] {
            height: 44px !important;
            font-size: 12px !important;
            padding: 12px 16px !important;
            border-radius: 8px !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
            text-align: center;
        }
        
        /* تنسيق ثابت للـ textarea */
        textarea.w-full.rounded-lg.border.border-gray-300.px-3.py-2.focus\:outline-none.bg-white.text-xs.peer {
            min-height: 44px !important;
            font-size: 12px !important;
            padding: 12px 16px !important;
            border-radius: 8px !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
            text-align: center;
            resize: vertical;
        }
        
        /* تنسيق ثابت لجميع الحقول */
        .grid.md\:grid-cols-3.gap-3 > div {
            min-height: 60px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        
        /* ضمان ثبات تنسيق الـ grid */
        .grid.md\:grid-cols-3.gap-3 {
            align-items: end;
            gap: 12px !important;
        }
        
        /* تنسيق ثابت للـ container */
        .relative.mt-3 {
            min-height: 60px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        
        /* تنسيق ثابت للـ focus state */
        input:focus,
        select:focus,
        textarea:focus {
            outline: none !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        
        /* تنسيق ثابت للـ hover state */
        input:hover,
        select:hover,
        textarea:hover {
            border-color: #9ca3af !important;
        }
        
        /* تنسيق موحد لجميع أنواع الحقول */
        input, select, textarea, button {
            font-family: inherit !important;
            box-sizing: border-box !important;
        }
        
        /* تنسيق خاص للـ grid items */
        .grid.md\:grid-cols-3.gap-3 > div {
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-end !important;
            min-height: 60px !important;
        }
        
        /* تنسيق ثابت للـ flex containers */
        .flex.gap-2.mb-2 {
            margin-bottom: 0 !important;
            align-items: stretch !important;
        }
        
        /* تنسيق ثابت للـ flex items */
        .flex.gap-2.mb-2 > button {
            flex: 1 !important;
            margin: 0 !important;
        }
        
        /* تنسيق ثابت للـ password container */
        .relative {
            position: relative !important;
        }
        
        /* تنسيق ثابت للـ password input container */
        .w-1\/2 {
            width: 50% !important;
        }
        
        /* تنسيق ثابت للـ password input */
        .w-1\/2 input {
            padding-right: 40px !important;
        }
        
        /* تنسيق ثابت للـ eye icon */
        .absolute.inset-y-0.left-2 {
            left: 8px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
        }
        
        /* تنسيق ثابت للـ md:col-span-2 */
        .md\:col-span-2 {
            grid-column: span 2 / span 2 !important;
        }
        
        /* تنسيق ثابت للـ w-full */
        .w-full {
            width: 100% !important;
        }
        
        /* تنسيق ثابت للـ md:w-1\/3 */
        .md\:w-1\/3 {
            width: 33.333333% !important;
        }
        
        /* تنسيق ثابت للـ md:w-\[82\%\] */
        .md\:w-\[82\%\] {
            width: 82% !important;
        }
        
        /* تنسيق خاص لقسم بيانات الأدمن */
        .border-t.border-gray-200.pt-6.mt-6 {
            margin-top: 1.5rem !important;
            padding-top: 1.5rem !important;
        }
        
        /* تنسيق خاص للحقول في صف واحد */
        .flex.flex-wrap.md\:flex-nowrap.justify-between.items-end.gap-4 {
            align-items: flex-end !important;
            gap: 1rem !important;
        }
        
        /* تنسيق خاص للحقول الأربعة */
        .flex.flex-wrap.md\:flex-nowrap.gap-2.w-full.md\:w-\[70\%\] {
            gap: 0.5rem !important;
        }
        
        /* تنسيق خاص لكل حقل في الصف */
        .w-full.md\:w-1\/4 {
            width: 25% !important;
        }
        
        /* تنسيق خاص لزر الحفظ */
        .w-full.md\:w-\[30\%\] {
            width: 30% !important;
        }
        
        /* تنسيق خاص لزر الحفظ */
        .w-full.md\:w-\[30\%\] button {
            height: 44px !important;
            font-size: 12px !important;
            padding: 12px 24px !important;
            border-radius: 8px !important;
            font-weight: 500 !important;
        }
        
        /* تنسيق خاص للـ password fields في الصف */
        .w-full.md\:w-1\/4 .relative {
            width: 100% !important;
        }
        
        /* تنسيق خاص للـ password input في الصف */
        .w-full.md\:w-1\/4 input[type="password"] {
            width: 100% !important;
        }
        
        /* تنسيق خاص لضمان محاذاة جميع الحقول في نفس السطر */
        .flex.flex-wrap.md\:flex-nowrap.justify-between.items-end.gap-4 > div {
            display: flex !important;
            align-items: flex-end !important;
        }
        
        /* تنسيق خاص للحقول الأربعة لضمان المحاذاة */
        .flex.flex-wrap.md\:flex-nowrap.gap-2.w-full.md\:w-\[70\%\] > div {
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-end !important;
            min-height: 60px !important;
        }
        
        /* تنسيق خاص للـ password container */
        .w-full.md\:w-1\/4 .relative {
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-end !important;
            min-height: 60px !important;
        }
        
        /* تنسيق خاص للـ password input container */
        .w-full.md\:w-1\/4 .relative .relative {
            width: 100% !important;
            position: relative !important;
        }
        
        /* تنسيق خاص لضمان محاذاة الـ labels */
        .w-full.md\:w-1\/4 .absolute.right-3.-top-2\.5.px-1.bg-white.text-xs.text-gray-500.transition-all.peer-focus\:-top-2\.5.peer-focus\:text-xs {
            position: absolute !important;
            top: -0.5rem !important;
            right: 0.75rem !important;
        }
        
        /* تنسيق خاص لضمان محاذاة رسائل الخطأ */
        .w-full.md\:w-1\/4 .absolute.-bottom-4.right-0.text-red-600.text-xs.mt-1 {
            position: absolute !important;
            bottom: -1rem !important;
            right: 0 !important;
        }
        
        /* تنسيق خاص لضمان ارتفاع موحد لجميع الحقول */
        .w-full.md\:w-1\/4 {
            height: 60px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-end !important;
        }
        
        /* تنسيق خاص لضمان محاذاة الـ input fields */
        .w-full.md\:w-1\/4 input,
        .w-full.md\:w-1\/4 select,
        .w-full.md\:w-1\/4 textarea {
            margin-bottom: 0 !important;
        }
        
        /* تنسيق خاص لضمان محاذاة الـ container الرئيسي */
        .flex.flex-wrap.md\:flex-nowrap.justify-between.items-end.gap-4 {
            align-items: flex-end !important;
            min-height: 60px !important;
        }
        
        /* تنسيق خاص لضمان محاذاة الـ password fields */
        .w-full.md\:w-1\/4 .relative {
            height: 44px !important;
            margin-bottom: 0 !important;
        }
        
        /* تنسيق خاص لضمان محاذاة الـ eye icon */
        .w-full.md\:w-1\/4 .absolute.inset-y-0.left-2 {
            top: 50% !important;
            transform: translateY(-50%) !important;
            left: 8px !important;
        }
        
        /* تنسيق خاص لضمان محاذاة الـ button container */
        .w-full.md\:w-\[30\%\] {
            height: 60px !important;
            display: flex !important;
            align-items: flex-end !important;
        }
        
        /* تنسيق خاص لضمان محاذاة زر الحفظ */
        .w-full.md\:w-\[30\%\] button {
            margin-bottom: 0 !important;
            align-self: flex-end !important;
        }
        
        /* تنسيق خاص للعنوان في الوسط */
        .flex.justify-between.items-center.mb-4 {
            position: relative !important;
        }
        
        /* تنسيق خاص للعنوان */
        .flex.justify-between.items-center.mb-4 h2 {
            position: absolute !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            width: auto !important;
            text-align: center !important;
        }
        
        /* تنسيق خاص لشرح النجمة */
        .flex.justify-between.items-center.mb-4 span {
            position: absolute !important;
            right: 0 !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
        }
        
        /* تنسيق placeholder في الوسط */
        input::placeholder,
        select::placeholder {
            text-align: center;
            color: #9ca3af;
        }
        
        /* تنسيق النص عند الكتابة */
        input:focus,
        select:focus,
        textarea:focus {
            text-align: center !important;
        }
        
        /* تنسيق placeholder مع النجمة الحمراء */
        input::placeholder,
        select::placeholder,
        textarea::placeholder {
            text-align: center;
            color: #9ca3af;
        }
        
        /* تنسيق خاص للـ placeholder مع النجمة الحمراء */
        input[placeholder*="*"]::placeholder,
        select[placeholder*="*"]::placeholder {
            color: #9ca3af;
        }
        
        /* تنسيق النجمة الحمراء في الـ placeholder */
        input[placeholder*="*"]::placeholder,
        select[placeholder*="*"]::placeholder {
            background: linear-gradient(to right, #9ca3af 0%, #9ca3af calc(100% - 8px), #ef4444 calc(100% - 8px), #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* إخفاء الـ labels قبل التركيز */
        .absolute.right-3.-top-2\.5.px-1.bg-white.text-xs.text-gray-500.transition-all.peer-focus\:-top-2\.5.peer-focus\:text-xs {
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }
        
        /* إظهار الـ labels عند التركيز */
        .peer:focus + .absolute.right-3.-top-2\.5.px-1.bg-white.text-xs.text-gray-500.transition-all.peer-focus\:-top-2\.5.peer-focus\:text-xs,
        .peer:not(:placeholder-shown) + .absolute.right-3.-top-2\.5.px-1.bg-white.text-xs.text-gray-500.transition-all.peer-focus\:-top-2\.5.peer-focus\:text-xs {
            opacity: 1;
            visibility: visible;
            top: -0.5rem;
            font-size: 0.75rem;
        }
        /* أزرار نوع الوكالة بتدرج وألوان الثيم */
        .agency-type-btn-theme {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1.5px solid #d1d5db;
            background: #fff;
            font-size: 13px;
            font-family: inherit;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s;
            margin-inline-end: 0.5rem;
        }
        .agency-type-btn-theme.active {
            background: linear-gradient(90deg, rgb(var(--primary-500)), rgb(var(--primary-600)));
            color: rgb(var(--primary-900));
            border-color: rgb(var(--primary-500));
            box-shadow: 0 2px 8px rgba(var(--primary-500), 0.08);
        }
        .agency-type-btn-theme:hover {
            background: linear-gradient(90deg, rgb(var(--primary-400)), rgb(var(--primary-600)));
            color: rgb(var(--primary-900));
            border-color: rgb(var(--primary-400));
        }
        /* رسالة الخطأ inline بجانب الحقل */
        .inline-error {
            display: inline-block;
            margin-inline-start: 1rem;
            color: #dc2626;
            font-size: 12px;
            vertical-align: middle;
        }
    </style>
</div>
