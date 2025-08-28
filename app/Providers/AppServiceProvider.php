<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

use App\Services\ThemeService;
use App\Models\Sale;
use App\Observers\SaleObserver;
use App\Models\Collection;
use App\Observers\CollectionObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Observers
        Sale::observe(SaleObserver::class);
        if (class_exists(Collection::class) && class_exists(CollectionObserver::class)) {
            Collection::observe(CollectionObserver::class);
        }

        // Gate: يظهر/يسمح بواجهة طلبات الموافقة فقط لمن لديه تسلسل موافقات في وكالته
        Gate::define('approvals.access', function ($user) {
            // اسمح لـ super-admin دائماً (اختياري)
            if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return true;
            }

            return DB::table('approval_sequence_users as asu')
                ->join('approval_sequences as s', 's.id', '=', 'asu.approval_sequence_id')
                ->where('asu.user_id', $user->id)
                ->where('s.agency_id', $user->agency_id)
                ->exists();
        });

        // تحديث آخر نشاط للمستخدم
        if (Auth::check()) {
            Auth::user()->update(['last_activity_at' => now()]);
        }

        // تمرير ألوان الثيم لجميع الصفحات
        try {
            View::composer('*', function ($view) {
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
