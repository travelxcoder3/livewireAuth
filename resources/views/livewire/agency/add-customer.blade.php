<div class="space-y-6">
    <!-- العنوان والتنبيه -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة العملاء
        </h2>

        @if(session('success'))
            <div class="bg-white rounded-md px-4 py-2 text-center shadow text-sm" style="color: rgb(var(--primary-700)); border: 1px solid rgba(var(--primary-200), 0.5);">
                {{ session('success') }}
            </div>
        @endif
    </div>

    <!-- نموذج الإضافة / التعديل -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <h2 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
            {{ $editingId ? 'تعديل عميل' : 'إضافة عميل جديد' }}
        </h2>

        <form wire:submit.prevent="save" class="space-y-4 text-sm">
            @php
                $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                $containerClass = 'relative mt-1';
            @endphp

            <div class="grid md:grid-cols-4 gap-3">
                <div class="{{ $containerClass }}">
                    <input type="text" wire:model.defer="name" class="{{ $fieldClass }}" placeholder="الاسم الكامل" />
                    <label class="{{ $labelClass }}">الاسم الكامل</label>
                    @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="{{ $containerClass }}">
                    <input type="email" wire:model.defer="email" class="{{ $fieldClass }}" placeholder="البريد الإلكتروني" />
                    <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                    @error('email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="{{ $containerClass }}">
                    <input type="text" wire:model.defer="phone" class="{{ $fieldClass }}" placeholder="رقم الهاتف" />
                    <label class="{{ $labelClass }}">رقم الهاتف</label>
                    @error('phone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="{{ $containerClass }}">
                    <input type="text" wire:model.defer="address" class="{{ $fieldClass }}" placeholder="العنوان" />
                    <label class="{{ $labelClass }}">العنوان</label>
                    @error('address') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- الأزرار -->
            <div class="flex flex-col sm:flex-row justify-center gap-3 pt-4">
                <button type="submit"
                    class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm w-full sm:w-auto"
                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                    {{ $editingId ? 'تأكيد التعديل' : 'حفظ العميل' }}
                </button>

                <button type="button" wire:click="resetFields"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm w-full sm:w-auto">
                    تنظيف الحقول
                </button>
            </div>
        </form>
    </div>

    <!-- جدول العملاء -->
    @php
        use App\Tables\CustomerTable;
        $columns = CustomerTable::columns();
    @endphp
    <x-data-table :rows="$customers" :columns="$columns" />

    <!-- Toast Success -->
    @if(session()->has('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 2000)"
             x-show="show"
             x-transition
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
             style="background-color: rgb(var(--primary-500));">
            {{ session('success') }}
        </div>
    @endif

    <!-- Floating Label CSS -->
    <style>
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
        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }
        button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>
</div>
