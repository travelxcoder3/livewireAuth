<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckAgencySubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // إذا المستخدم من وكالة ولديه نهاية اشتراك
        if ($user && $user->agency && $user->agency->subscription_end_date) {
            $today = Carbon::today();
            if ($today->greaterThan($user->agency->subscription_end_date)) {
                // يتم تسجيل الخروج وإعادة التوجيه
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'subscription' => 'انتهت فترة اشتراك وكالتك. يرجى التواصل مع الدعم لتجديد الاشتراك.',
                ]);
            }
        }

        return $next($request);
    }
}