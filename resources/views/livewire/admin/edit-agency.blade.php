<div class="p-8 max-w-2xl mx-auto">
    @if(session('message'))
        <div class="mb-4 p-3 bg-emerald-100 text-emerald-800 rounded-lg text-center">
            {{ session('message') }}
        </div>
    @endif
    <h2 class="text-2xl font-bold text-emerald-700 mb-6 text-center">تعديل بيانات الوكالة</h2>
    <form wire:submit.prevent="updateAgency" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">اسم الوكالة</label>
                <input type="text" wire:model.defer="agency_name" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                @error('agency_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">عدد المستخدمين المسموحين</label>
                <input type="number" min="1" wire:model.defer="max_users" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                @error('max_users') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">البريد الإلكتروني للوكالة</label>
                <input type="email" wire:model.defer="agency_email" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                @error('agency_email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">رقم الهاتف</label>
                <input type="text" wire:model.defer="agency_phone" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                @error('agency_phone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">العنوان</label>
                <input type="text" wire:model.defer="agency_address" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                @error('agency_address') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">رقم الرخصة</label>
                <input type="text" wire:model.defer="license_number" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                @error('license_number') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">السجل التجاري</label>
                <input type="text" wire:model.defer="commercial_record" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                @error('commercial_record') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">الرقم الضريبي</label>
                <input type="text" wire:model.defer="tax_number" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                @error('tax_number') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ انتهاء الرخصة</label>
                <input type="date" wire:model.defer="license_expiry_date" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                @error('license_expiry_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">وصف الوكالة (اختياري)</label>
            <textarea wire:model.defer="description" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"></textarea>
            @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="mt-8 flex justify-center gap-4">
            <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white px-8 py-3 rounded-lg font-bold text-lg transition duration-200 shadow-lg hover:shadow-xl">حفظ التعديلات</button>
            <a href="{{ route('admin.agencies') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-8 py-3 rounded-lg font-bold text-lg transition duration-200 text-center">إلغاء</a>
        </div>
    </form>
</div> 