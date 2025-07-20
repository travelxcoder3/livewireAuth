@props([
    'href' => null,
    'icon' => '',
    'label' => '',
    'tooltip' => '',
    'dropdown' => false,
    'class' => '',
])

<div class="relative group">
    @if ($href)
        <a href="{{ $href }}"
           class="flex items-center px-2 py-1 rounded-full bg-white/10 hover:bg-white/20 transition focus:outline-none focus:ring-0 focus:border-0 border-0 ring-0 outline-none {{ $class }} {{ request()->routeIs('agency.dashboard') && $label == 'الرئيسية' ? 'active' : '' }}"
           @if ($tooltip) title="{{ $tooltip }}" @endif {{ $attributes }}>

            {{-- الأيقونة --}}
            <span class="nav-icon icon-button">
                @if ($icon)
                    <i class="{{ $icon }} text-white text-base"></i>
                @endif
                {{ $slot }}
            </span>

            {{-- النص --}}
            @if ($label)
                <span class="nav-text text-xs text-white whitespace-nowrap ml-2 transition-all duration-300 ease-in-out opacity-0 group-hover:opacity-100 group-hover:ml-3">
                    {{ $label }}
                </span>
            @endif

            {{-- سهم القائمة المنسدلة --}}
            @if ($dropdown)
                <span class="ml-1 text-xs text-white">
                    <i class="fas fa-chevron-down"></i>
                </span>
            @endif
        </a>
    @else
        <button type="button"
                class="flex items-center px-2 py-1 rounded-full bg-white/10 hover:bg-white/20 transition focus:outline-none focus:ring-0 focus:border-0 border-0 ring-0 outline-none {{ $class }}"
                @if ($tooltip) title="{{ $tooltip }}" @endif {{ $attributes }}>

            {{-- الأيقونة --}}
            <span class="nav-icon icon-button">
                @if ($icon)
                    <i class="{{ $icon }} text-white text-base"></i>
                @endif
                {{ $slot }}
            </span>

            {{-- النص --}}
            @if ($label)
                <span class="nav-text text-xs text-white whitespace-nowrap mr-2 transition-all duration-300 ease-in-out opacity-0 group-hover:opacity-100 group-hover:mr-3">
                    {{ $label }}
                </span>
            @endif

            {{-- سهم القائمة المنسدلة --}}
            @if ($dropdown)
                <span class="ml-1 text-xs text-white">
                    <i class="fas fa-chevron-down"></i>
                </span>
            @endif
        </button>
    @endif

    {{-- القائمة المنسدلة --}}
    @if (trim($slot) && $dropdown)
        <div class="absolute left-0 top-full mt-2 bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block min-w-[180px]">
            {{ $slot }}
        </div>
    @endif
</div>

<!-- @if($href ?? null)
    <a
        href="{{ $href }}"
        class="flex items-center px-2 py-1 rounded-full bg-white/10 hover:bg-white/20 transition focus:outline-none focus:ring-0 focus:border-0 border-0 ring-0 outline-none {{ $class ?? '' }} {{ request()->routeIs('agency.dashboard') && $label == 'الرئيسية' ? 'active' : '' }}"
        @if($tooltip ?? null) title="{{ $tooltip }}" @endif
        {{ $attributes }}
    >
        <span class="nav-icon">
            @if (trim($slot))
                {{ $slot }}
            @elseif($icon)
                <i class="{{ $icon }} text-white text-base"></i>
            @endif
        </span>
        @if($label)
            <span class="nav-text text-xs text-white whitespace-nowrap mr-2">{{ $label }}</span>
        @endif
        @if($dropdown ?? false)
            <span class="ml-1 text-xs text-white">
                <i class="fas fa-chevron-down"></i>
            </span>
        @endif
    </a>
@else
    <button type="button"
        class="flex items-center px-2 py-1 rounded-full bg-white/10 hover:bg-white/20 transition focus:outline-none focus:ring-0 focus:border-0 border-0 ring-0 outline-none {{ $class ?? '' }}"
        @if($tooltip ?? null) title="{{ $tooltip }}" @endif
        {{ $attributes }}>
        <span class="nav-icon">
            @if (trim($slot))
                {{ $slot }}
            @elseif($icon)
                <i class="{{ $icon }} text-white text-base"></i>
            @endif
        </span>
        @if($label)
            <span class="nav-text text-xs text-white whitespace-nowrap mr-2">{{ $label }}</span>
        @endif
        @if($dropdown ?? false)
            <span class="ml-1 text-xs text-white">
                <i class="fas fa-chevron-down"></i>
            </span>
        @endif
    </button>
@endif  -->