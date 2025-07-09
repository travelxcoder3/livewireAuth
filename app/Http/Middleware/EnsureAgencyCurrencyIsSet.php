<?php
// app/Http/Middleware/EnsureAgencyCurrencyIsSet.php
// app/Http/Middleware/EnsureAgencyCurrencyIsSet.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAgencyCurrencyIsSet
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if ($user && $user->agency && !$user->agency->currency && !$request->is('agency/setup-currency')) {
            return redirect()->route('agency.setup-currency');
        }

        return $next($request);
    }
}
