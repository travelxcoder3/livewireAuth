@php
    use App\Services\ThemeService;

    $themeName = ThemeService::getSystemTheme();
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp
<div>
<div class="flex flex-col h-screen overflow-hidden bg-gray-50">
    <!-- القسم العلوي -->
    <div class="flex-none p-4">
        <div class="bg-white rounded-xl shadow-md p-8 max-w-md mx-auto">
            <!-- العنوان والأيقونة -->
            <div class="text-center mb-8">
                <div class="mx-auto h-16 w-16 rounded-full flex items-center justify-center mb-4"
                     style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-1">تسجيل الدخول</h2>
                <p class="text-gray-600">مرحباً بعودتك! أدخل بياناتك للمتابعة</p>
                @if (session()->has('error'))
                    <div class="text-red-600 text-sm mb-4 p-3 bg-red-50 rounded-lg text-center">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            <!-- رسائل الخطأ -->
            @if ($errors->has('subscription'))
                <div class="text-red-600 text-sm mb-4 p-3 bg-red-50 rounded-lg text-center">
                    {{ $errors->first('subscription') }}
                </div>
            @endif

            <!-- نموذج تسجيل الدخول -->
            <form wire:submit.prevent="login" class="space-y-6">
                <!-- حقل البريد الإلكتروني -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600 mb-2">البريد الإلكتروني</label>
                    <div class="relative">
                        <input
                            wire:model="email"
                            type="email"
                            id="email"
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none bg-white placeholder-gray-400 text-gray-600 @error('email') border-red-400 focus:ring-red-200 focus:border-red-400 @enderror"
                            placeholder="example@email.com"
                            dir="ltr"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                        </div>
                    </div>
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- حقل كلمة المرور -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600 mb-2">كلمة المرور</label>
                    <div class="relative">
                        <input
                            wire:model="password"
                            type="{{ $showPassword ? 'text' : 'password' }}"
                            id="password"
                            class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none bg-white placeholder-gray-400 text-gray-600 @error('password') border-red-400 focus:ring-red-200 focus:border-red-400 @enderror"
                            placeholder="••••••••"
                            dir="ltr"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <button
                            type="button"
                            wire:click="togglePassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-500 focus:outline-none"
                        >
                            @if($showPassword)
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                </svg>
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            @endif
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- التذكر ونسيت كلمة المرور -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            wire:model="remember"
                            id="remember"
                            type="checkbox"
                            class="h-4 w-4 text-primary-500 focus:ring-primary-300 border-gray-300 rounded"
                        >
                        <label for="remember" class="mr-2 block text-sm text-gray-600">تذكرني</label>
                    </div>
                    <div class="text-sm">
                        <a href="/forgot-password" class="font-medium text-primary-500 hover:text-primary-600 transition">نسيت كلمة المرور؟</a>
                    </div>
                </div>

                <!-- زر تسجيل الدخول -->
                <div>
                    <button
                        type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg shadow-sm text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150"
                        style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));"
                    >
                        تسجيل الدخول
                    </button>
                </div>
            </form>

           

            <!-- حقوق النشر -->
            <div class="text-center mt-8">
                <p class="text-xs text-gray-500">© {{ date('Y') }} جميع الحقوق محفوظة</p>
            </div>
        </div>
    </div>
</div>

<style>
    input:focus, select:focus, textarea:focus {
        border-color: rgb({{ $colors['primary-500'] }}) !important;
        box-shadow: 0 0 0 2px rgba({{ $colors['primary-500'] }}, 0.2) !important;
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