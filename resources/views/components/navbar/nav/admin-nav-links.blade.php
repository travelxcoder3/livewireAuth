@php
    $navLinks = [
        ['route' => 'admin.dashboard', 'label' => 'الرئيسية', 'icon' => 'home'],
        ['route' => 'admin.agencies', 'label' => 'إدارة الوكالات', 'icon' => 'building'],
        ['route' => 'admin.add-agency', 'label' => 'إضافة وكالة', 'icon' => 'fa-solid fa-folder-plus'],
        ['route' => 'admin.dynamic-lists', 'label' => 'القوائم', 'icon' => 'bars'],
    ];
@endphp

<div class="flex items-center gap-1 sm:gap-2 h-full">
    @foreach ($navLinks as $link)
        <x-navbar.buttons.icon-button
            :href="route($link['route'])"
            :label="$link['label']"
            class="nav-item flex items-center px-2 py-1 rounded-full {{ request()->routeIs($link['route'] . '*') ? 'bg-white/20' : '' }}">

            <i class="fa-solid fa-{{ $link['icon'] }} text-white text-lg"></i>
        </x-navbar.buttons.icon-button>
    @endforeach
</div>
