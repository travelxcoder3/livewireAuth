@php
    use App\Services\ThemeService;
    $theme = ThemeService::getSystemTheme();
    $themeColors = ThemeService::getCurrentThemeColors($theme);
    $primaryColor = $themeColors['primary-500'] ?? '16, 185, 129';
@endphp
@if($show ?? true)
    @if($href && $href !== '#')
        <a href="{{ $href }}" class="dropdown-link-hover block px-4 py-2 flex items-center gap-2 transition-colors duration-150" style="--dropdown-hover-bg: rgb({{ $primaryColor }});">
            @if(trim($slot))
                {{ $slot }}
            @else
                <i class="{{ $icon }}"></i> {{ $label }}
            @endif
        </a>
    @else
        <span class="dropdown-link-hover block px-4 py-2 flex items-center gap-2 cursor-default transition-colors duration-150" style="--dropdown-hover-bg: rgb({{ $primaryColor }});">
            @if(trim($slot))
                {{ $slot }}
            @else
                <i class="{{ $icon }}"></i> {{ $label }}
            @endif
        </span>
    @endif
@endif 