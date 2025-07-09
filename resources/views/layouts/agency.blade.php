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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
    --primary-100: {{ $colors['primary-100'] }};
    --primary-500: {{ $colors['primary-500'] }};
    --primary-600: {{ $colors['primary-600'] }};
}   

        .nav-gradient {
            background: linear-gradient(90deg, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);
        }

        .border-theme {
            border-color: rgb(var(--primary-500));
        }

        .focus-ring-theme:focus {
            ring-color: rgb(var(--primary-500));
        }

        .nav-item { transition: all 0.2s ease; }
        .nav-item.active { background-color: rgba(255,255,255,0.2); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .nav-item:hover { background-color: rgba(255,255,255,0.1); }

        .nav-text {
            transition: opacity 0.2s ease, max-width 0.2s ease;
            opacity: 0;
            max-width: 0;
            overflow: hidden;
        }
        .nav-item.active .nav-text,
        .nav-item:hover .nav-text { opacity: 1; max-width: 100px; }

        .nav-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            transition: all 0.2s ease;
        }
        .nav-item:hover .nav-icon,
        .nav-item.active .nav-icon { background-color: rgba(255,255,255,0.2); }

        .user-dropdown-menu {
            display: none;
            position: absolute;
            left: 0;
            top: 110%;
            margin-top: 8px;
            min-width: 180px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.08);
            z-index: 50;
        }
        .group-user-dropdown:focus-within .user-dropdown-menu,
        .group-user-dropdown:hover .user-dropdown-menu { display: block; }
    </style>
</head>
<body class="bg-dashboard min-h-screen font-app">
    @include('layouts.partials.nav')

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