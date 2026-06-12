<?php

use App\Http\Middleware\SyncHospitalSession;
use App\Http\Middleware\UpdateUserLastSeen;
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
        $middleware->alias([
            'grade.access' => \App\Http\Middleware\EnsureGradeRouteAccess::class,
        ]);

        $middleware->web(append: [
            SyncHospitalSession::class,
            UpdateUserLastSeen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
