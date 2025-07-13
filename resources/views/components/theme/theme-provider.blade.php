@php
    use App\Services\ThemeService;

    if (Auth::check()) {
        if (Auth::user()->hasRole('super-admin')) {
            $themeName = ThemeService::getSystemTheme();
        } else {
            $themeName = strtolower(Auth::user()->agency->theme_color ?? 'emerald');
        }
    } else {
        $themeName = 'emerald';
    }

    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp

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
</style>
