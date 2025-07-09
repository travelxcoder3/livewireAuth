<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Laravel Livewire' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    @auth
        <div class="flex justify-end items-center p-4">
            <div class="relative group" tabindex="0">
                <button class="inline-flex items-center justify-center h-11 w-11 rounded-full border-2 border-emerald-500 bg-gradient-to-tr from-emerald-100 to-teal-100 shadow focus:outline-none focus:ring-2 focus:ring-emerald-300 transition" tabindex="0">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'User') }}&background=10b981&color=fff&rounded=true&size=64"
                         alt="avatar"
                         class="h-9 w-9 rounded-full object-cover border-2 border-white shadow" />
                </button>
                <div class="absolute left-0 top-14 min-w-[220px] bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 text-left hidden group-focus-within:block group-hover:block transition-all">
                    <div class="flex items-center gap-3 px-5 pt-5 pb-3 border-b border-gray-100 bg-gradient-to-tr from-emerald-50 to-white rounded-t-2xl">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'User') }}&background=10b981&color=fff&rounded=true&size=48"
                             alt="avatar"
                             class="h-10 w-10 rounded-full object-cover border-2 border-emerald-200 shadow" />
                        <div>
                            <div class="font-bold text-gray-800 text-base leading-tight">{{ Auth::user()->name ?? 'User Name' }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ Auth::user()->role->name ?? 'الدور غير محدد' }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full text-left px-5 py-4 text-red-600 hover:bg-red-50 font-semibold transition rounded-b-2xl">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1" />
                            </svg>
                            تسجيل الخروج
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endauth
    {{ $slot }}
    @livewireScripts
</body>
</html> 