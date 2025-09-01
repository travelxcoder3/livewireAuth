<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AutoPermission
{
    protected array $abilityMap = [
        'index'=>'view','show'=>'view','list'=>'view',
        'create'=>'create','store'=>'create',
        'edit'=>'edit','update'=>'edit',
        'destroy'=>'delete','delete'=>'delete',
        'pdf'=>'export','excel'=>'export','export'=>'export','print'=>'export','download'=>'export','restore'=>'export','run'=>'export',
    ];

    protected array $adminRoles = ['super-admin','agency-admin'];

    // مسارات يُسمح بها بدون صلاحية
    protected array $bypass = [
        'agency.change-password',
        'agency.profile',
        'agency.notifications.index',
    ];
//     protected array $bypass = [
//     'agency.dashboard',
//     'agency.change-password',
//     'agency.profile',
//     'agency.notifications.index',
// ];


    // استثناء: اسم روت ⇒ اسم صلاحية
    protected array $overrides = [
        // 'agency.reports.provider-ledger' => 'reports.provider-ledger.view',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) abort(403);

        foreach ($this->adminRoles as $role) {
            if ($user->hasRole($role)) return $next($request);
        }

        $route = $request->route();
        $name  = $route?->getName();

        if ($name && in_array($name, $this->bypass, true)) {
            return $next($request);
        }

        if ($name && isset($this->overrides[$name])) {
            abort_unless($user->can($this->overrides[$name]), 403);
            return $next($request);
        }

        if ($name) {
            $parts = explode('.', $name);

            // احذف بادئات عامة
            if ($parts && ($parts[0] === 'agency' || $parts[0] === 'admin')) {
                array_shift($parts);
            }

            // تحديد module + action بذكاء
            if (count($parts) === 0) {
                $module = 'dashboard';
                $action = 'index';
            } elseif (count($parts) === 1) {
                $module = $parts[0];   // مثال: agency.users ⇒ users.index
                $action = 'index';
            } else {
                $action = array_pop($parts) ?: 'index';
                $module = implode('.', $parts);
            }

            $ability = $this->abilityMap[$action] ?? 'view';
            $perm    = "{$module}.{$ability}";
            abort_unless($user->can($perm), 403);
            return $next($request);
        }

        // fallback لو ما في اسم روت
        $seg = $request->segment(2) ?: 'dashboard'; // بعد /agency
        abort_unless($user->can("{$seg}.view"), 403);
        return $next($request);
    }
}
