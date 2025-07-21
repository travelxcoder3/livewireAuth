<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateLastActivity
{
    public function handle(Request $request, Closure $next)
    {
     

       if (Auth::check()) {
    Auth::user()->update([
        'last_activity_at' => now(),
    ]);

}


        return $next($request);
    }
}
