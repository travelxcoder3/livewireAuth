@php
    use App\Services\ThemeService;

    if (Auth::check()) {
        $themeName = Auth::user()->hasRole('super-admin')
            ? ThemeService::getSystemTheme()
            : strtolower(Auth::user()->agency->theme_color ?? 'emerald');
    } else {
        $themeName = 'emerald';
    }

    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'إدارة الوكالة' }}</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @stack('styles')
    @include('layouts.partials.common')

    <style>
        /* احذف جميع الأكواد هنا لأنها موجودة في common.blade.php */
    </style>
</head>
<body class="bg-dashboard min-h-screen font-app" x-data="{ mobileSidebarOpen: false }">
    <!-- الشريط العلوي -->
    <nav class="w-full flex items-center justify-between px-6 shadow-sm rounded-t-2xl nav-gradient"
        style="padding-top: 8px; padding-bottom: 8px; min-height:48px;">

        <!-- زر القائمة الجانبية للجوال -->
        <button class="lg:hidden flex items-center justify-center mr-2 text-white focus:outline-none" @click="mobileSidebarOpen = true">
            <i class="fas fa-bars text-2xl"></i>
        </button>

        <!-- الشعار -->
        <x-navbar.brand.agency-brand />

        <!-- روابط التنقل -->
        <div class="hidden lg:flex">
            <x-navbar.nav.agency-nav-links />
        </div>

        <!-- أدوات التحكم -->
        <div class="hidden lg:flex">
            <x-navbar.nav.agency-topbar-controls />
        </div>
    </nav>

    <!-- القائمة الجانبية للموبايل -->
    <div x-show="mobileSidebarOpen" x-transition>
        <x-navbar.agency-mobile-sidebar @close="mobileSidebarOpen = false" />
    </div>

    <main class="w-full px-4 py-6">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-[rgba(var(--primary-500),0.2)] p-6">
                {{ $slot }}
        </div>
    </main>

    @livewireScripts

<script>
function updateTheme(theme) {
    fetch('/update-theme', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ theme_color: theme })
            }).then(response => {
        if (response.ok) {
            window.location.reload();
        } else {
                    alert('فشل في تغيير الثيم');
                }
            }).catch(() => alert('فشل الاتصال بالخادم'));
        }

        document.addEventListener('click', e => {
    if (!e.target.closest('.group-theme-selector')) {
                document.querySelector('.theme-selector-menu')?.classList.add('hidden');
    }
});

        document.querySelector('.group-theme-selector button')?.addEventListener('click', e => {
    e.stopPropagation();
            document.querySelector('.theme-selector-menu')?.classList.toggle('hidden');
});
</script>
</body>
</html>
