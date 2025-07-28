@php
    use App\Services\ThemeService;

    $themeName = ThemeService::getSystemTheme();
    $colors = ThemeService::getCurrentThemeColors($themeName);

    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] bg-white text-xs peer';
    $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
    $containerClass = 'relative mt-0.5';
@endphp



<div>
<div class="flex flex-col min-h-screen overflow-y-auto">
    <x-toast />

    <!-- القسم العلوي الثابت -->
    <div class="flex-none p-0 bg-gray-50">
        <!-- نموذج إضافة الوكالة -->
        <div class="bg-white rounded-xl shadow-md p-4">
        <div class="flex items-center justify-between mb-4 flex-wrap md:flex-nowrap gap-2">
            <h2 class="text-xl font-bold text-black text-center md:text-right w-full md:w-auto flex-1">
                إضافة وكالة جديدة وتعيين أدمن للوكالة
            </h2>
            <div class="flex items-center text-sm font-normal text-red-600 whitespace-nowrap">
                <span>الحقول المطلوبة *</span>
            </div>
        </div>




            <form wire:submit.prevent="save" class="space-y-4 text-sm" id="mainForm">
                @php
                        $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none bg-white text-xs peer';
                        $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs';
                    $containerClass = 'relative mt-0.5';
                @endphp

                    <!-- الحقول -->
                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_name" class="{{ $fieldClass }}"  />
                            <label class="{{ $labelClass }}">اسم الوكالة <span class="text-red-500">*</span></label>
                            @error('agency_name')     <span class="text-red-600 text-xs mt-1 block">
                                    {{ $message }}
                                </span>
                            @enderror

                    </div>
                      <div class="{{ $containerClass }}" x-data="{ open: false, search: '', selectedId: @entangle('parent_id'), selectedLabel: '' }">
                        <!-- عنوان الحقل -->
                        <label class="{{ $labelClass }}">نوع الوكالة <span class="text-red-500">*</span></label>

                        <!-- الزرين: وكالة رئيسية / فرع -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2 mt-1">
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

                        </div>

                        <!-- إذا كانت الوكالة فرعية -->
                        @if(!$isMainAgency)
                            <div class="mt-2 relative">
                                <!-- الزر لفتح القائمة -->
                                <div @click="open = !open"
                                    class="{{ $fieldClass }} cursor-pointer bg-white border border-gray-300 px-4 py-2 rounded text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))]">
                                    <span x-text="selectedLabel || 'اختر الوكالة الرئيسية *'"></span>
                                </div>

                                <!-- القائمة المنسدلة -->
                                <div x-show="open" @click.outside="open = false"
                                    x-transition
                                    class="absolute z-50 mt-1 w-full bg-white border rounded shadow-xl max-h-60 overflow-auto">
                                    <!-- مربع البحث -->
                                    <div class="p-2 border-b">
                                        <input type="text" x-model="search"
                                          
                                            class="w-full px-2 py-1 border rounded text-sm focus:outline-none focus:ring-1 focus:ring-[rgb(var(--primary-500))]">
                                    </div>

                                    <!-- قائمة الوكالات -->
                                    <ul class="text-sm max-h-48 overflow-y-auto">
                                        @foreach($mainAgencies as $agency)
                                            <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                                                x-show="'{{ $agency->name }}'.toLowerCase().includes(search.toLowerCase())"
                                                @click="
                                                    selectedId = '{{ $agency->id }}';
                                                    selectedLabel = '{{ $agency->name }}';
                                                    open = false;
                                                ">
                                                {{ $agency->name }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <!-- القيمة الحقيقية المرتبطة بـ Livewire -->
                                <input type="hidden" x-model="selectedId" wire:model.defer="parent_id" />
                            </div>

                            <!-- عرض الخطأ -->
                            @error('parent_id')
                                <span class="inline-error text-red-600 text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>


                        <div class="{{ $containerClass }}">
                            <input type="email" wire:model.defer="agency_email" class="{{ $fieldClass }}" />
                            <label class="{{ $labelClass }}">البريد الإلكتروني <span class="text-red-500">*</span></label>
                            @error('agency_email')     <span class="text-red-600 text-xs mt-1 block">
                                    {{ $message }}
                                </span>
                            @enderror

                            </div>
                    </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_phone" class="{{ $fieldClass }}" />
                            <label class="{{ $labelClass }}" >   رقم الهاتف المحمول<span class="text-red-500">*</span></label>
                            @error('agency_phone')     <span class="text-red-600 text-xs mt-1 block">
                                    {{ $message }}
                                </span>
                            @enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="landline" class="{{ $fieldClass }}"  />
                            <label class="{{ $labelClass }}">الهاتف الثابت</label>
                        @error('landline')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <select wire:model.defer="currency" class="{{ $fieldClass }}">
                                <option value="">اختر العمله المستخدمه في النظام *</option>
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="USD">دولار أمريكي (USD)</option>
                            <option value="EUR">يورو (EUR)</option>
                        </select>
                            <label class="{{ $labelClass }}">العملة <span class="text-red-500">*</span></label>
                        @error('currency')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="agency_address" class="{{ $fieldClass }}"  />
                            <label class="{{ $labelClass }}">العنوان <span class="text-red-500">*</span></label>
                        @error('agency_address')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="license_number" class="{{ $fieldClass }}"  />
                            <label class="{{ $labelClass }}"> الرقم الترخيص للوكالة  <span class="text-red-500">*</span></label>
                        @error('license_number')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="commercial_record" class="{{ $fieldClass }}"  />
                            <label class="{{ $labelClass }}">  رقم السجل التجاري<span class="text-red-500">*</span></label>
                        @error('commercial_record')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="text" wire:model.defer="tax_number" class="{{ $fieldClass }}"/>
                            <label class="{{ $labelClass }}">الرقم الضريبي <span class="text-red-500">*</span></label>
                        @error('tax_number')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model.defer="license_expiry_date" class="{{ $fieldClass }}" />
                            <label class="{{ $labelClass }}">  تاريخ انتهاء الرخصة</label>
                        @error('license_expiry_date')     <span class="text-red-600 text-xs mt-1 block">
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
                        @error('status')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="file" wire:model="logo" class="{{ $fieldClass }}" />

                            <label class="{{ $labelClass }}">شعار الوكالة</label>
                            @error('logo')     <span class="text-red-600 text-xs mt-1 block">
                                    {{ $message }}
                                </span>
                            @enderror

                    </div>
                        <div class="{{ $containerClass }} md:col-span-2">
                            <textarea wire:model.defer="description" rows="2" class="{{ $fieldClass }}"></textarea>
                            <label class="{{ $labelClass }}">وصف الوكالة</label>
                        @error('description')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model.defer="subscription_start_date" class="{{ $fieldClass }}"  />
                            <label class="{{ $labelClass }}">تاريخ بدايه الاشتراك </label>
                        @error('subscription_start_date')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model.defer="subscription_end_date" class="{{ $fieldClass }}"  />
                            <label class="{{ $labelClass }}">تاريخ نهاية الاشتراك</label>
                        @error('subscription_end_date')     <span class="text-red-600 text-xs mt-1 block">
                                {{ $message }}
                            </span>
                        @enderror


                        </div>
                                        <!-- ... الحقول الأخرى ... -->
                <div class="{{ $containerClass }}">
                    <input type="number" wire:model.defer="max_users" class="{{ $fieldClass }}" min="1" max="100" />
                    <label class="{{ $labelClass }}">الحد الأقصى لمستخدمي الوكالة <span class="text-red-500">*</span></label>
                    @error('max_users')     <span class="text-red-600 text-xs mt-1 block">
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
                            <input type="text" wire:model.defer="admin_name" class="{{ $fieldClass }}"  />
                            <label class="{{ $labelClass }}">اسم الأدمن <span class="text-red-500">*</span></label>
                            @error('admin_name')     <span class="text-red-600 text-xs mt-1 block">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="{{ $containerClass }} w-full md:w-1/4">
                            <input type="email" wire:model.defer="admin_email" class="{{ $fieldClass }}"  />
                            <label class="{{ $labelClass }}">بريد الأدمن <span class="text-red-500">*</span></label>
                            @error('admin_email')     <span class="text-red-600 text-xs mt-1 block">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="{{ $containerClass }} w-full md:w-1/4">
                            <div class="relative" x-data="{ show: false }">
                                <input :type="show ? 'text' : 'password'" wire:model.defer="admin_password" class="{{ $fieldClass }} pr-10" />
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
                                <input :type="show ? 'text' : 'password'" wire:model.defer="admin_password_confirmation" class="{{ $fieldClass }} pr-10"  />
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
                    <div class="w-full md:w-[25%]">
                       <x-primary-button
                            type="submit"
                            class="w-full px-6 py-3 hover:opacity-80 hover:shadow-lg"
                        >
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
    .agency-type-btn-theme {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
        border: 1px solid rgb(var(--primary-500));
        background-color: white;
        color: rgb(var(--primary-700));
    }

    /* الزر المحدد يظل مميز دائمًا */
    .agency-type-btn-theme.active {
        background-color: rgb(var(--primary-600));
        color: white;
        border: 1px solid rgb(var(--primary-500)); /* حدود بلون الثيم */
    }

    /* عند المرور (فقط على غير المحدد) */
    .agency-type-btn-theme:hover:not(.active) {
        background-color: rgb(var(--primary-100));
        color: rgb(var(--primary-800));
    }
</style>

</div>
