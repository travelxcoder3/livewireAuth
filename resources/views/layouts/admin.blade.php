<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'إدارة النظام' }}</title>
    <!-- Font Awesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <x-theme.theme-provider />

    <style>
        body.bg-dashboard {
            background: #e7e8fd !important;
        }

        .nav-item {
            transition: all 0.2s ease;
        }

        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

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
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            z-index: 50;
        }

        .group-user-dropdown:hover .user-dropdown-menu,
        .group-user-dropdown:focus-within .user-dropdown-menu {
            display: block;
        }
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
        <x-navbar.nav.admin-nav-links />
        </div>

        <!-- أدوات التحكم -->
        <div class="hidden lg:flex">
        <x-navbar.nav.topbar-controls />
        </div>
    </nav>

    <!-- القائمة الجانبية للموبايل -->
    <div x-show="mobileSidebarOpen" x-transition>
        <x-navbar.admin-mobile-sidebar @close="mobileSidebarOpen = false" />
    </div>

    <!-- المحتوى -->
    <div class="flex-1 w-full px-0 py-8">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-emerald-100 p-8 w-full">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
    <script>
        function updateSystemTheme(theme) {
            fetch('{{ route('admin.system.update-theme') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        theme_color: theme
                    })
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
