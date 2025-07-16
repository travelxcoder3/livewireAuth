<div class="p-8 max-w-2xl mx-auto">
    @if(session('message'))
        <div class="mb-4 p-3 bg-emerald-100 text-emerald-800 rounded-lg text-center">
            {{ session('message') }}
        </div>
    @endif
    <h2 class="text-2xl font-bold text-black mb-6 text-center">تعديل بيانات الوكالة</h2>
    <form wire:submit.prevent="updateAgency" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">اسم الوكالة</label>
                <input type="text" wire:model.defer="agency_name" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('agency_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">البريد الإلكتروني للوكالة</label>
                <input type="email" wire:model.defer="agency_email" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('agency_email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">رقم الهاتف</label>
                <input type="text" wire:model.defer="agency_phone" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('agency_phone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">الهاتف الثابت</label>
                <input type="text" wire:model.defer="landline" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('landline') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">العملة</label>
                <select wire:model.defer="currency" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme">
                    <option value="SAR">ريال سعودي (SAR)</option>
                    <option value="USD">دولار أمريكي (USD)</option>
                    <option value="EUR">يورو (EUR)</option>
                </select>
                @error('currency') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">العنوان</label>
                <input type="text" wire:model.defer="agency_address" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('agency_address') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">رقم الرخصة</label>
                <input type="text" wire:model.defer="license_number" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('license_number') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">السجل التجاري</label>
                <input type="text" wire:model.defer="commercial_record" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('commercial_record') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">الرقم الضريبي</label>
                <input type="text" wire:model.defer="tax_number" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('tax_number') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ انتهاء الرخصة</label>
                <input type="date" wire:model.defer="license_expiry_date" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('license_expiry_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">عدد المستخدمين المسموحين</label>
                <input type="number" min="1" wire:model.defer="max_users" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('max_users') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">حالة الوكالة</label>
                <select wire:model.defer="status" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme">
                    <option value="active">نشطة</option>
                    <option value="inactive">غير نشطة</option>
                    <option value="suspended">موقوفة</option>
                </select>
                @error('status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ بداية الاشتراك</label>
                <input type="date" wire:model.defer="subscription_start_date" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('subscription_start_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ نهاية الاشتراك</label>
                <input type="date" wire:model.defer="subscription_end_date" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme" />
                @error('subscription_end_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">وصف الوكالة (اختياري)</label>
            <textarea wire:model.defer="description" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-theme focus:border-theme"></textarea>
            @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="mt-8 flex justify-center gap-4">
            <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white px-8 py-3 rounded-lg font-bold text-lg transition duration-200 shadow-lg hover:shadow-xl">حفظ التعديلات</button>
            <a href="{{ route('admin.agencies') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-8 py-3 rounded-lg font-bold text-lg transition duration-200 text-center">إلغاء</a>
        </div>
    </form>
    <style>
    input:focus, select:focus, textarea:focus {
        border-color: rgb(var(--primary-500)) !important;
        box-shadow: 0 0 0 2px rgba(var(--primary-500), 0.2) !important;
    }
    button[type="submit"], .btn-theme {
        background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%) !important;
        color: #fff;
    }
    button[type="submit"]:hover, .btn-theme:hover {
        box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
    }
    .text-theme { color: rgb(var(--primary-500)) !important; }
    .border-theme { border-color: rgb(var(--primary-500)) !important; }
    .focus-ring-theme:focus { box-shadow: 0 0 0 2px rgba(var(--primary-500), 0.2) !important; }
    </style>
</div> 