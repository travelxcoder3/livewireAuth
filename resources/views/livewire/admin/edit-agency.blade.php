<div>
<div class="flex flex-col h-screen overflow-hidden">
    <!-- المحتوى القابل للتمرير -->
    <div class="flex-1 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-md min-h-full flex flex-col p-6">
               <x-toast />






            <h2 class="text-2xl font-bold text-black mb-6 text-center">تعديل بيانات الوكالة: {{ $agency_name ?? '' }}</h2>

            @php
                $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                $containerClass = 'relative mt-1';
            @endphp

            <form wire:submit.prevent="updateAgency" class="space-y-4 text-sm">
                <!-- الصف الأول - 4 حقول -->
                <div class="grid md:grid-cols-4 gap-3">
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="agency_name" class="{{ $fieldClass }}" placeholder="اسم الوكالة">
                        <label class="{{ $labelClass }}">اسم الوكالة</label>
                        @error('agency_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="email" wire:model.defer="agency_email" class="{{ $fieldClass }}" placeholder="البريد الإلكتروني">
                        <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                        @error('agency_email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="agency_phone" class="{{ $fieldClass }}" placeholder="رقم الهاتف">
                        <label class="{{ $labelClass }}">رقم الهاتف</label>
                        @error('agency_phone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="landline" class="{{ $fieldClass }}" placeholder="الهاتف الثابت">
                        <label class="{{ $labelClass }}">الهاتف الثابت</label>
                        @error('landline') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- الصف الثاني - 4 حقول -->
                <div class="grid md:grid-cols-4 gap-3">
                    <div class="{{ $containerClass }}">
                        <select wire:model.defer="currency" class="{{ $fieldClass }}">
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="USD">دولار أمريكي (USD)</option>
                            <option value="EUR">يورو (EUR)</option>
                        </select>
                        <label class="{{ $labelClass }}">العملة</label>
                        @error('currency') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="agency_address" class="{{ $fieldClass }}" placeholder="العنوان">
                        <label class="{{ $labelClass }}">العنوان</label>
                        @error('agency_address') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="license_number" class="{{ $fieldClass }}" placeholder="رقم الرخصة">
                        <label class="{{ $labelClass }}">رقم الرخصة</label>
                        @error('license_number') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="commercial_record" class="{{ $fieldClass }}" placeholder="السجل التجاري">
                        <label class="{{ $labelClass }}">السجل التجاري</label>
                        @error('commercial_record') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- الصف الثالث - 4 حقول -->
                <div class="grid md:grid-cols-4 gap-3">
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="tax_number" class="{{ $fieldClass }}" placeholder="الرقم الضريبي">
                        <label class="{{ $labelClass }}">الرقم الضريبي</label>
                        @error('tax_number') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="date" wire:model.defer="license_expiry_date" class="{{ $fieldClass }}">
                        <label class="{{ $labelClass }}">انتهاء الرخصة</label>
                        @error('license_expiry_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="number" min="1" wire:model.defer="max_users" class="{{ $fieldClass }}" placeholder="عدد المستخدمين">
                        <label class="{{ $labelClass }}">عدد المستخدمين</label>
                        @error('max_users') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
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

                <!-- الصف الرابع - 4 حقول -->
                <div class="grid md:grid-cols-4 gap-3">
                    <div class="{{ $containerClass }}">
                        <input type="date" wire:model.defer="subscription_start_date" class="{{ $fieldClass }}">
                        <label class="{{ $labelClass }}">بداية الاشتراك</label>
                        @error('subscription_start_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <input type="date" wire:model.defer="subscription_end_date" class="{{ $fieldClass }}">
                        <label class="{{ $labelClass }}">نهاية الاشتراك</label>
                        @error('subscription_end_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <select wire:model.defer="parent_agency_id" class="{{ $fieldClass }}">
                            <option value="">بدون وكالة أب</option>
                            @foreach($agenciesList as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <label class="{{ $labelClass }}">الوكالة الأب</label>
                        @error('parent_agency_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{ $containerClass }}">
                        <!-- حقل إضافي إذا لزم الأمر -->
                    </div>
                </div>

                <!-- حقل الوصف -->
                <div class="grid md:grid-cols-1 gap-3">
                    <div class="{{ $containerClass }}">
                        <textarea wire:model.defer="description" rows="2" class="{{ $fieldClass }}" placeholder="وصف الوكالة"></textarea>
                        <label class="{{ $labelClass }}">الوصف (اختياري)</label>
                        @error('description') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- أزرار الحفظ والإلغاء -->
                <div class="flex justify-center gap-4 mt-6 pb-4">
                    <button type="submit"
                        class="text-white font-bold px-6 py-2 rounded-lg shadow-md transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        حفظ التعديلات
                    </button>
                    <a href="{{ route('admin.agencies') }}"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold px-6 py-2 rounded-lg transition duration-300 text-sm text-center">
                        إلغاء
                    </a>
                </div>
            </form>
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
</dvi>