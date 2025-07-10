<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'إدارة النظام' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <x-theme-provider />

    <style>
        body.bg-dashboard { background: #e7e8fd !important; }

        .nav-item { transition: all 0.2s ease; }
        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .nav-item:hover { background-color: rgba(255, 255, 255, 0.1); }

        .nav-text {
            transition: opacity 0.2s ease, max-width 0.2s ease;
            opacity: 0;
            max-width: 0;
            overflow: hidden;
        }
        .nav-item.active .nav-text,
        .nav-item:hover .nav-text {
            opacity: 1;
            max-width: 100px;
        }

        .nav-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }
        .nav-item:hover .nav-icon,
        .nav-item.active .nav-icon {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .user-dropdown-menu {
            display: none;
            position: absolute;
            left: 0;
            top: 110%;
            margin-top: 8px;
            min-width: 180px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            z-index: 50;
        }

        .group-user-dropdown:hover .user-dropdown-menu,
        .group-user-dropdown:focus-within .user-dropdown-menu {
            display: block;
        }
    </style>
</head>

<body class="bg-dashboard min-h-screen font-app">
    <!-- الشريط العلوي -->
    <nav class="w-full flex items-center justify-between px-6 shadow-sm rounded-t-2xl nav-gradient"
         style="padding-top: 8px; padding-bottom: 8px; min-height:48px;">

        <!-- الشعار -->
        <div class="flex items-center gap-3">
            <svg class="h-9 w-9" viewBox="0 0 32 32" fill="none">
                <rect x="2" y="8" width="28" height="20" rx="6" fill="rgb(var(--primary-500))"/>
                <rect x="8" y="14" width="4" height="4" rx="1" fill="#fff"/>
                <rect x="14" y="14" width="4" height="4" rx="1" fill="#fff"/>
                <rect x="20" y="14" width="4" height="4" rx="1" fill="#fff"/>
                <rect x="12" y="22" width="8" height="4" rx="2" fill="rgb(var(--primary-600))"/>
            </svg>
            <span class="text-white text-lg font-bold tracking-tight">
                {{ Auth::user()->agency->name ?? 'لوحة الإدارة' }}
            </span>
        </div>

        <!-- روابط التنقل -->
        <div class="flex items-center gap-1 sm:gap-2 h-full">
            @php
                $navLinks = [
                    ['route' => 'admin.dashboard', 'label' => 'الرئيسية', 'icon' => 'M3 12l9-9 9 9M4 10v10a1 1 0 001 1h3m10-11v11a1 1 0 01-1 1h-3m-4 0h4'],
                    ['route' => 'admin.agencies', 'label' => 'إدارة الوكالات', 'icon' => 'M4 7V6a2 2 0 012-2h3V3a1 1 0 112 0v1h2V3a1 1 0 112 0v1h3a2 2 0 012 2v1M4 7h16v13a2 2 0 01-2 2H6a2 2 0 01-2-2V7z'],
                    ['route' => 'admin.add-agency', 'label' => 'إضافة وكالة', 'icon' => 'M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm6 3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0z'],
                    ['route' => 'admin.dynamic-lists', 'label' => 'القوائم','icon' => 'M5 6h14M5 12h14M5 18h14M8 6V4m0 2v2m0-2h1m-1 4v2m0-2v2m0-2h1m-1 4v2m0-2v2m0-2h1'],
                ];
            @endphp

            @foreach($navLinks as $link)
                <a href="{{ route($link['route']) }}"
                   class="nav-item flex items-center px-2 py-1 rounded-full {{ request()->routeIs($link['route'] . '*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
                        </svg>
                    </span>
                    <span class="nav-text text-xs text-white whitespace-nowrap mr-2">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </div>

        <!-- أدوات التحكم -->
        <div class="flex items-center gap-2 sm:gap-4">
            <x-theme-selector />

            <!-- اللغة -->
            <span class="flex items-center justify-center h-10 w-10 rounded-full bg-white/10">
                <svg class="h-6 w-6 rounded-full" viewBox="0 0 24 24"><rect width="24" height="24" fill="#fff"/><path d="M0 0h24v24H0z" fill="#00247d"/><path d="M0 0l24 24M24 0L0 24" stroke="#fff" stroke-width="2"/><path d="M0 0l24 24M24 0L0 24" stroke="#cf142b" stroke-width="1"/><rect x="10" width="4" height="24" fill="#fff"/><rect y="10" width="24" height="4" fill="#fff"/><rect x="11" width="2" height="24" fill="#cf142b"/><rect y="11" width="24" height="2" fill="#cf142b"/></svg>
            </span>

            <!-- المستخدم -->
            <div class="relative group-user-dropdown" tabindex="0">
                <button class="flex items-center justify-center h-10 w-10 rounded-full border-2 border-theme bg-white/10 focus:outline-none focus:ring-2 focus-ring-theme">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4" fill="rgb(var(--primary-500))"/>
                        <rect x="5" y="15" width="14" height="6" rx="3" fill="rgb(var(--primary-600))"/>
                        <circle cx="12" cy="8" r="3.2" fill="#fff"/>
                    </svg>
                </button>
                <div class="user-dropdown-menu">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <div class="font-bold text-gray-800 text-base">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ Auth::user()->role->name ?? 'مستخدم' }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="w-full text-right px-4 py-2 text-red-600 hover:bg-red-50 font-semibold transition">تسجيل الخروج</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- المحتوى -->
    <div class="flex-1 w-full px-0 py-8">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-emerald-100 p-8 w-full">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
    <script>
    function updateSystemTheme(theme) {
        fetch('{{ route("admin.system.update-theme") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ theme_color: theme })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) window.location.reload();
            else alert(data.message || 'فشل تغيير الثيم');
        })
        .catch(error => {
            console.error(error);
            alert('حدث خطأ أثناء تغيير اللون');
        });
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.group-theme-selector')) {
            document.querySelector('.theme-selector-menu')?.classList.add('hidden');
        }
    });

    document.querySelector('.group-theme-selector button')?.addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelector('.theme-selector-menu')?.classList.toggle('hidden');
    });
    </script>
</body>
</html>
