@php
    $navLinks = [
        ['route' => 'admin.dashboard', 'label' => 'الرئيسية', 'icon' => 'M3 12l9-9 9 9M4 10v10a1 1 0 001 1h3m10-11v11a1 1 0 01-1 1h-3m-4 0h4'],
        ['route' => 'admin.agencies', 'label' => 'إدارة الوكالات', 'icon' => 'M4 7V6a2 2 0 012-2h3V3a1 1 0 112 0v1h2V3a1 1 0 112 0v1h3a2 2 0 012 2v1M4 7h16v13a2 2 0 01-2 2H6a2 2 0 01-2-2V7z'],
        ['route' => 'admin.add-agency', 'label' => 'إضافة وكالة', 'icon' => 'M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm6 3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0z'],
        ['route' => 'admin.dynamic-lists', 'label' => 'القوائم','icon' => 'M5 6h14M5 12h14M5 18h14M8 6V4m0 2v2m0-2h1m-1 4v2m0-2v2m0-2h1m-1 4v2m0-2v2m0-2h1'],
    ];
@endphp

<div class="flex items-center gap-1 sm:gap-2 h-full">
    @foreach($navLinks as $link)
        <x-icon-button
            :href="route($link['route'])"
            :label="$link['label']"
            class="nav-item flex items-center px-2 py-1 rounded-full {{ request()->routeIs($link['route'] . '*') ? 'active' : '' }}"
        >
            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
            </svg>
        </x-icon-button>
    @endforeach
</div> 