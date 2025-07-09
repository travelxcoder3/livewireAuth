<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // استثناء السوبر أدمن من تغيير كلمة المرور
        if ($user && $user->hasRole('super-admin')) {
            return $next($request);
        }

        // فرض تغيير كلمة المرور فقط على أدمن الوكالة
        if ($user && $user->hasRole('agency-admin') && $user->must_change_password && !$request->is('agency/change-password')) {
            return redirect()->route('agency.change-password');
        }

        return $next($request);
    }
}
