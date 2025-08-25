<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceAgencyScope
{
    private array $modelParamHints = [
        'user','customer','sale','provider','quotation','invoice','account','wallet',
        'employee','collection','statement','target'
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) abort(401);

        $currentAgencyId = $user->agency_id ?? null;

        if ($request->route('agency')) {
            $routeAgency = $request->route('agency');
            $routeAgencyId = is_object($routeAgency) ? ($routeAgency->id ?? $routeAgency->getKey()) : (int) $routeAgency;
            if ($currentAgencyId && $routeAgencyId !== (int) $currentAgencyId) {
                abort(403, 'Forbidden: cross-agency route.');
            }
        }

        foreach ($request->route()->parameters() as $key => $param) {
            if (!in_array($key, $this->modelParamHints, true)) continue;
            if (is_object($param) && isset($param->agency_id)) {
                if ((int) $param->agency_id !== (int) $currentAgencyId) {
                    abort(403, 'Forbidden: record not in your agency.');
                }
            }
        }

        return $next($request);
    }
}
