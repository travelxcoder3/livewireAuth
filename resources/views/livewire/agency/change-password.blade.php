@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp

<div>
    <div class="flex justify-center items-center min-h-screen px-4" style="min-height: calc(100vh - 80px);">
        <div class="bg-white rounded-xl shadow-md p-6 w-full max-w-md">
            <!-- العنوان -->
            <h2 class="text-xl font-bold text-black text-center mb-4">
                تغيير كلمة المرور
            </h2>

            <!-- رسالة النجاح -->
            @if (session()->has('success'))
                <div class="text-green-600 text-center font-semibold mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- النموذج -->
            <form wire:submit.prevent="updatePassword" class="space-y-4 text-sm">
                @php
                    $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none bg-white text-xs peer';
                    $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all 
                                  peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb('.$colors['primary-600'].')]';
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
                    class="w-full text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm hover:shadow-lg"
                    style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}) 0%, rgb({{ $colors['primary-600'] }}) 100%);">
                    تحديث كلمة المرور
                </button>
            </form>
        </div>
    </div>

    <!-- التنسيقات -->
    <style>
        input:focus {
            border-color: rgb({{ $colors['primary-500'] }}) !important;
            box-shadow: 0 0 0 2px rgba({{ $colors['primary-500'] }}, 0.2) !important;
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
            color: rgb({{ $colors['primary-500'] }}) !important;
        }

        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba({{ $colors['primary-500'] }}, 0.2);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>
</div>
