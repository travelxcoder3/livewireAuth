@if($href ?? null)
    <a
        href="{{ $href }}"
        class="flex items-center px-2 py-1 rounded-full bg-white/10 hover:bg-white/20 transition {{ $class ?? '' }} {{ request()->routeIs('agency.dashboard') && $label == 'الرئيسية' ? 'active' : '' }}"
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
        class="flex items-center px-2 py-1 rounded-full bg-white/10 hover:bg-white/20 transition {{ $class ?? '' }}"
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
@endif 