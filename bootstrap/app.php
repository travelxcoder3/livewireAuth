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
    $middleware->append(\App\Http\Middleware\UpdateLastActivity::class);
    $middleware->appendToGroup('web', \App\Http\Middleware\DefaultAgencyParam::class);

    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'ensureCurrency' => \App\Http\Middleware\EnsureAgencyCurrencyIsSet::class,
        'mustChangePassword' => \App\Http\Middleware\EnsurePasswordIsChanged::class,
        'check.agency.subscription' => \App\Http\Middleware\CheckAgencySubscription::class,
        'active.user' => \App\Http\Middleware\UpdateLastActivity::class,
        'agency.scope' => \App\Http\Middleware\EnforceAgencyScope::class,
        'auto_permission' => \App\Http\Middleware\AutoPermission::class,


    ]);
})

    ->withCommands([
        \App\Console\Commands\AgencyBackup::class,
        \App\Console\Commands\AgencyRestore::class,
        \App\Console\Commands\AgencyBackupAll::class,      
        \App\Console\Commands\AgencyPruneBackups::class,
        \App\Console\Commands\BackupFull::class,         // الجديد
    \App\Console\Commands\RestoreFull::class,  
    ])

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
