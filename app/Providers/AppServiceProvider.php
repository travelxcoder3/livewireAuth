<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\ThemeService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
public function boot()
{
    try {
        view()->composer('*', function ($view) {
            if (auth()->check()) {
                $theme = auth()->user()->hasRole('super-admin') 
                    ? ThemeService::getSystemTheme()
                    : (auth()->user()->agency->theme_color ?? 'emerald');
                
                $colors = ThemeService::getCurrentThemeColors($theme);
                $view->with('themeColors', $colors);
            }
        });
    } catch (\Exception $e) {
        Log::error('Theme provider error: ' . $e->getMessage());
    }
}
}
// public function boot()
// {
//     try {
//         view()->composer('*', function ($view) {
//             if (auth()->check()) {
//                 $theme = auth()->user()->isSuperAdmin() 
//                     ? ThemeService::getSystemTheme()
//                     : (auth()->user()->agency->theme_color ?? 'emerald');
                
//                 $colors = ThemeService::getCurrentThemeColors($theme);
//                 $view->with('themeColors', $colors);
//             }
//         });
//     } catch (\Exception $e) {
//         Log::error('Theme provider error: ' . $e->getMessage());
//     }
// }
// }