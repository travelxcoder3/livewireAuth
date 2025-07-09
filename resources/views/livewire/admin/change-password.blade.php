<div>
<div class="flex justify-center items-center min-h-screen px-4" style="min-height: calc(100vh - 80px);">
    <div class="bg-white rounded-xl shadow-md p-6 w-full max-w-md">
        <h2 class="text-xl font-bold text-emerald-700 text-center mb-4">تغيير كلمة المرور</h2>

        @if (session()->has('success'))
            <div class="text-green-600 text-center font-semibold mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="updatePassword" class="space-y-4 text-sm">
            @php
                $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none bg-white text-xs peer';
                $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-emerald-600';
                $containerClass = 'relative mt-1';
            @endphp

            <!-- كلمة المرور الجديدة -->
            <div class="{{ $containerClass }}">
                <input type="password" wire:model.defer="password" class="{{ $fieldClass }}" placeholder="كلمة المرور الجديدة" />
                <label class="{{ $labelClass }}">كلمة المرور الجديدة</label>
                @error('password') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- تأكيد كلمة المرور -->
            <div class="{{ $containerClass }}">
                <input type="password" wire:model.defer="password_confirmation" class="{{ $fieldClass }}" placeholder="تأكيد كلمة المرور" />
                <label class="{{ $labelClass }}">تأكيد كلمة المرور</label>
            </div>

            <!-- زر التحديث -->
            <button type="submit"
                class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold px-4 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm">
                تحديث كلمة المرور
            </button>
        </form>
    </div>

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
            color: #059669;
        }
    </style>
</div>
</div> 