<div>
<div class="flex flex-col h-screen overflow-hidden">
    <!-- المحتوى القابل للتمرير -->
    <div class="flex-1 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-md min-h-full flex flex-col p-6">
            <!-- صورة الشعار -->
<div class="flex justify-center mb-6">
    <div class="relative">
@if ($tempLogoUrl)
    <img src="{{ $tempLogoUrl }}"
         class="w-32 h-32 rounded-full border-4 border-white shadow-lg object-cover">
@else
    <img src="{{ $logoPreview }}"
         class="w-32 h-32 rounded-full border-4 border-white shadow-lg object-cover">
@endif




        <div class="absolute bottom-0 right-0 bg-white p-2 rounded-full shadow-md">
            <label class="cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" style="color: rgb(var(--primary-600));" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <input type="file" wire:model="logo" class="hidden" accept="image/*">
            </label>
        </div>
    </div>
</div>


            <!-- النموذج -->
            <form wire:submit.prevent="update" class="space-y-4 text-sm"   enctype="multipart/form-data" >
                @php
                    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                    $readonlyFieldClass = 'w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800';
                    $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                    $containerClass = 'relative mt-1';
                @endphp

                <!-- صف الحقول الثابتة -->
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="{{ $containerClass }}">
                        <div class="{{ $readonlyFieldClass }}">{{ $agency->name ?? 'غير محدد' }}</div>
                        <label class="{{ $labelClass }}">اسم الوكالة</label>
                    </div>
                    <div class="{{ $containerClass }}">
                        <div class="{{ $readonlyFieldClass }}">{{ $agency->currency ?? 'غير محدد' }}</div>
                        <label class="{{ $labelClass }}">العملة</label>
                    </div>
                </div>

                <!-- صف الحقول الثابتة الثانية -->
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="{{ $containerClass }}">
                        <div class="{{ $readonlyFieldClass }}">{{ $agency->license_number ?? 'غير محدد' }}</div>
                        <label class="{{ $labelClass }}">رقم الرخصة</label>
                    </div>
                    <div class="{{ $containerClass }}">
                        <div class="{{ $readonlyFieldClass }}">{{ $agency->commercial_record ?? 'غير محدد' }}</div>
                        <label class="{{ $labelClass }}">السجل التجاري</label>
                    </div>
                    <div class="{{ $containerClass }}">
                        <div class="{{ $readonlyFieldClass }}">{{ $agency->tax_number ?? 'غير محدد' }}</div>
                        <label class="{{ $labelClass }}">الرقم الضريبي</label>
                    </div>
                </div>

                <!-- صف الحقول الثابتة الثالثة -->
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="{{ $containerClass }}">
                        <div class="{{ $readonlyFieldClass }}">
                            {{ optional($agency->license_expiry_date)->format('Y-m-d') ?? 'غير محدد' }}
                        </div>
                        <label class="{{ $labelClass }}">انتهاء الرخصة</label>
                    </div>
                    <div class="{{ $containerClass }}">
                        <div class="{{ $readonlyFieldClass }}">{{ $agency->max_users ?? 'غير محدد' }}</div>
                        <label class="{{ $labelClass }}">عدد المستخدمين</label>
                    </div>
                    <div class="{{ $containerClass }}">
                        <div class="{{ $readonlyFieldClass }}">
                            @if($agency->status == 'active')
                                <span style="color: rgb(var(--primary-600));">نشطة</span>
                            @elseif($agency->status == 'inactive')
                                <span class="text-yellow-600">غير نشطة</span>
                            @elseif($agency->status == 'suspended')
                                <span class="text-red-600">موقوفة</span>
                            @else
                                غير محدد
                            @endif
                        </div>
                        <label class="{{ $labelClass }}">حالة الوكالة</label>
                    </div>
                </div>

                <!-- الحقول القابلة للتعديل -->
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model="phone" class="{{ $fieldClass }}" placeholder="أدخل رقم الهاتف">
                        <label class="{{ $labelClass }}">رقم الهاتف</label>
                        @error('phone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model="landline" class="{{ $fieldClass }}" placeholder="أدخل الهاتف الثابت">
                        <label class="{{ $labelClass }}">الهاتف الثابت</label>
                        @error('landline') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="email" wire:model="email" class="{{ $fieldClass }}" placeholder="أدخل البريد الإلكتروني">
                        <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                        @error('email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- العنوان والوصف -->
                <div class="grid md:grid-cols-1 gap-3">
                    <div class="{{ $containerClass }}">
                        <textarea wire:model="address" rows="2" class="{{ $fieldClass }}" placeholder="أدخل العنوان"></textarea>
                        <label class="{{ $labelClass }}">العنوان</label>
                        @error('address') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <textarea wire:model="description" rows="2" class="{{ $fieldClass }}" placeholder="أدخل الوصف"></textarea>
                        <label class="{{ $labelClass }}">الوصف</label>
                        @error('description') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- زر الحفظ -->
                @can('agency.profile.edit')
                <div class="flex justify-end mt-6 pb-4">
                  
                    <button type="submit"
                      wire:click="update"
                        class="text-white font-bold px-6 py-2 rounded-lg shadow-md transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        تعديل البيانات
                    </button>
                </div>
                @endcan
            </form>

            <!-- رسالة نجاح -->
            @if (session()->has('success'))
                <div class="mt-4 p-3 text-xs text-center rounded-lg"
                    style="background-color: rgba(var(--primary-100), 0.5); border: 1px solid rgba(var(--primary-200), 0.5); color: rgb(var(--primary-700));">
                    {{ session('success') }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- تحسين واجهة المستخدم -->
<style>
    html, body {
        height: 100%;
        overflow: hidden;
    }
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
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    button[type="submit"]:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
    }
    button[type="submit"]:active {
        transform: translateY(0);
    }
</style>


</div>