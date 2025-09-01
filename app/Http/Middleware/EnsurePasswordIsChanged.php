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

      // ✳️ عمومي لكل الأدوار: أي مستخدم عليه must_change_password يُحوّل لصفحة تغيير كلمة المرور
if ($user && $user->must_change_password) {
    if (
        !$request->routeIs('change-password') &&      // اسم المسار العام الجديد
        !$request->routeIs('password.*') &&           // نسمح بمسارات الاستعادة/إعادة التعيين
        !$request->routeIs('logout') &&               // نسمح بتسجيل الخروج
        !$request->is('agency/change-password')       // توافقًا مع المسار القديم داخل مجموعة agency
    ) {
        return redirect()->route('change-password');
    }
}

return $next($request);

    }
}
