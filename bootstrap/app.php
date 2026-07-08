<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php', 
        web: __DIR__ . '/../routes/web.php', 
        commands: __DIR__ . '/../routes/console.php', 
        health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'guru' => \App\Http\Middleware\GuruMiddleware::class,
            'siswa' => \App\Http\Middleware\SiswaMiddleware::class,
            'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
