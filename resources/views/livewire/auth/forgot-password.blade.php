<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>استعادة كلمة المرور</title>
    @php
        use App\Services\ThemeService;
        $themeName = ThemeService::getSystemTheme();
        $colors = ThemeService::getCurrentThemeColors($themeName);
    @endphp
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-50">

    <div class="flex flex-col h-screen overflow-hidden bg-gray-50">
        <div class="flex-none p-4">
            <div class="bg-white rounded-xl shadow-md p-8 max-w-md mx-auto">
                <!-- محتوى العنوان -->
                <div class="text-center mb-6">
                    <div class="mx-auto h-16 w-16 rounded-full flex items-center justify-center mb-4"
                        style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-1">استعادة كلمة المرور</h2>
                    <p class="text-gray-600">أدخل بريدك الإلكتروني لإرسال رابط إعادة تعيين كلمة المرور</p>
                </div>

                @if(session('message'))
                    <div class="bg-emerald-100 text-emerald-700 rounded-lg p-3 text-center mb-4">{{ session('message') }}</div>
                @endif

                <form wire:submit.prevent="sendResetLink" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-600 mb-2">البريد الإلكتروني</label>
                        <input wire:model="email" type="email" id="email"
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none bg-white placeholder-gray-400 text-gray-600"
                            placeholder="example@email.com" dir="ltr">
                    </div>
                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg shadow-sm text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150"
                            style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
                            إرسال رابط إعادة التعيين
                        </button>
                    </div>
                </form>

                <div class="text-center mt-6">
                    <a href="/login" class="text-sm font-bold text-primary-500 hover:text-primary-600 transition">
                        العودة لتسجيل الدخول
                    </a>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
