<div>
<div class="flex flex-col h-screen overflow-hidden">
    <!-- المحتوى القابل للتمرير -->
    <div class="flex-1 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-md min-h-full flex flex-col p-6">
            <!-- صورة الشعار -->
            <div class="flex justify-center mb-6">
                <div class="relative">
                    @if ($tempLogoUrl)
                        <img src="{{ $tempLogoUrl }}" class="w-32 h-32 rounded-full border-4 border-white shadow-lg object-cover">
                    @else
                        <img src="{{ $logoPreview }}" class="w-32 h-32 rounded-full border-4 border-white shadow-lg object-cover">
                    @endif

                    @if($editing)
                        <div class="absolute bottom-0 right-0 bg-white p-2 rounded-full shadow-md">
                            <label class="cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" style="color: rgb(var(--primary-600));" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <input type="file" wire:model="logo" class="hidden" accept="image/*">
                            </label>
                        </div>
                    @endif
                </div>
            </div>

            @if (session()->has('error'))
                <div class="mt-4 p-3 text-xs text-center rounded-lg bg-red-100 text-red-700 border border-red-300">
                    {{ session('error') }}
                </div>
            @endif

            <!-- عرض البيانات في وضع القراءة فقط -->
            <div class="space-y-4 text-sm" wire:key="read-only-view">
                <!-- صف الحقول الثابتة -->
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->name ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">اسم الوكالة</label>
                    </div>
                    
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->currency ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">العملة</label>
                    </div>
                    
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->license_number ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">رقم الرخصة</label>
                    </div>
                </div>

                <!-- صف الحقول الثابتة الثانية -->
                <div class="grid md:grid-cols-3 gap-3 mt-4">
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->commercial_record ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">السجل التجاري</label>
                    </div>
                    
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->tax_number ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">الرقم الضريبي</label>
                    </div>
                    
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ optional($agency->license_expiry_date)->format('Y-m-d') ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">انتهاء الرخصة</label>
                    </div>
                </div>

                <!-- صف الحقول الثابتة الثالثة -->
                <div class="grid md:grid-cols-3 gap-3 mt-4">
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->max_users ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">عدد المستخدمين</label>
                    </div>
                    
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
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
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">حالة الوكالة</label>
                    </div>
                    
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->phone ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">رقم الهاتف</label>
                    </div>
                </div>

                <!-- الحقول القابلة للتعديل في وضع القراءة -->
                <div class="grid md:grid-cols-3 gap-3 mt-4">
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->landline ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">الهاتف الثابت</label>
                    </div>
                    
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->email ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">البريد الإلكتروني</label>
                    </div>
                    
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                            {{ $agency->address ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">العنوان</label>
                    </div>
                </div>

                <!-- الوصف والهدف الشهري -->
                <div class="grid md:grid-cols-2 gap-3 mt-4">
                    <div class="relative mt-1">
                        <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800 min-h-[60px]">
                            {{ $agency->description ?? 'غير محدد' }}
                        </div>
                        <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">الوصف</label>
                    </div>
                    
                  <div class="relative mt-1">
                    <div class="w-full rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-800">
                        {{ $currentTarget ?? 'غير محدد' }}
                    </div>
                    <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">الهدف الشهري</label>
                </div>

                </div>
            </div>

            <!-- زر التعديل -->
            @can('agency.profile.edit')
            <div class="flex justify-end mt-6 pb-4">
               <button wire:click="startEditing"
                        class="text-white font-bold px-6 py-2 rounded-lg shadow-md transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        تعديل البيانات
                </button>
            </div>
            @endcan

            <!-- رسالة نجاح -->
            <!-- رسالة نجاح -->
                @if (session()->has('success'))
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition
                        class="fixed bottom-4 right-4 z-50 text-white px-4 py-2 rounded-md shadow text-sm"
                        style="background-color: rgb(var(--primary-500));">
                        {{ session('success') }}
                    </div>
                @endif
        </div>
    </div>

    <!-- نافذة التعديل المنبثقة -->
  
  @if($editing)
<div class="fixed inset-0 flex items-start justify-center pt-24 z-50 backdrop-blur-sm bg-white/10">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 border border-gray-200">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold" style="color: rgb(var(--primary-700));">تعديل بيانات الوكالة</h3>
            <button wire:click="cancelEditing" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
        </div>
        @if (session()->has('error'))
            <div class="mb-4 p-3 text-sm text-center rounded-lg bg-red-100 text-red-700 border border-red-300">
                {{ session('error') }}
            </div>
        @endif


            <form wire:submit.prevent="update" class="space-y-4 text-sm">
                <!-- صف الحقول القابلة للتعديل -->
                <div class="grid md:grid-cols-3 gap-3">
                    <x-input-field 
                        name="phone"
                        wireModel="phone"
                        label="رقم الهاتف"
                        placeholder="أدخل رقم الهاتف"
                        errorName="phone"
                    />
                    
                    <x-input-field 
                        name="landline"
                        wireModel="landline"
                        label="الهاتف الثابت"
                        placeholder="أدخل الهاتف الثابت"
                        errorName="landline"
                    />
                    
                    <x-input-field 
                        name="email"
                        wireModel="email"
                        label="البريد الإلكتروني"
                        placeholder="أدخل البريد الإلكتروني"
                        type="email"
                        errorName="email"
                    />
                </div>

                <div class="grid md:grid-cols-3 gap-3">
                    <x-input-field 
                        name="address"
                        wireModel="address"
                        label="العنوان"
                        placeholder="أدخل العنوان"
                        errorName="address"
                    />
                    
                    <x-input-field 
                        name="description"
                        wireModel="description"
                        label="الوصف"
                        placeholder="أدخل الوصف"
                        errorName="description"
                    />
                    
                    <x-input-field 
                        name="monthlyTarget"
                        wireModel="monthlyTarget"
                        label="الهدف الشهري"
                        placeholder="أدخل الهدف الشهري"
                        type="number"
                        errorName="monthlyTarget"
                    />
                </div>

                <!-- أزرار الحفظ والإلغاء -->
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="cancelEditing"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-lg shadow transition duration-300 text-sm">
                        إلغاء
                    </button>
                    <button type="submit"
                        class="text-white font-bold px-6 py-2 rounded-lg shadow-md transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

<!-- تحسين واجهة المستخدم -->
<style>
    html, body {
        height: 100%;
        overflow: hidden;
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
    button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
    }
    button:active {
        transform: translateY(0);
    }
    .modal-content {
        animation: modalFadeIn 0.3s ease-out;
    }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
document.addEventListener('livewire:load', function() {
    Livewire.on('message-shown', () => {
        setTimeout(() => {
            Livewire.emit('hideSuccessMessage');
        }, 3000);
    });
});
</script>
</div>