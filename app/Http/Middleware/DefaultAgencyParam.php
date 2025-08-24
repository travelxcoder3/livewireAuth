<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;

class DefaultAgencyParam
{
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            // يضبط بارامتر {agency} افتراضياً لكل المسارات المسماة التي تحتاجه
            URL::defaults(['agency' => auth()->user()->agency_id]);
        }

        return $next($request);
    }
}
