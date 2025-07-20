<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ميدل وير تحديث آخر نشاط المستخدم
        $middleware->append(\App\Http\Middleware\UpdateLastActivity::class);
        // تسجيل Middleware الخاص بـ Spatie role
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'ensureCurrency' => \App\Http\Middleware\EnsureAgencyCurrencyIsSet::class,
            'mustChangePassword' => \App\Http\Middleware\EnsurePasswordIsChanged::class,
            'check.agency.subscription' => \App\Http\Middleware\CheckAgencySubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
