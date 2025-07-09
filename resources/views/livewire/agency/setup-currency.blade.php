<div class="flex justify-center items-center min-h-screen px-4" style="min-height: calc(100vh - 80px);">
    <div class="bg-white rounded-xl shadow-md p-6 w-full max-w-md">
        <h2 class="text-xl font-bold text-emerald-700 text-center mb-4">اختر العملة الأساسية لوكالتك</h2>

        <form wire:submit.prevent="save" class="space-y-4 text-sm">
            @php
                $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none bg-white text-xs peer';
                $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-emerald-600';
                $containerClass = 'relative mt-1';
            @endphp

            <div class="{{ $containerClass }}">
                <select wire:model="currency" class="{{ $fieldClass }}">
                    <option value="" disabled>-- اختر العملة --</option>
                    @foreach ($currencies as $curr)
                        <option value="{{ $curr }}">{{ $curr }}</option>
                    @endforeach
                </select>
                <label class="{{ $labelClass }}">العملة</label>
                @error('currency') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm">
                حفظ العملة
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
