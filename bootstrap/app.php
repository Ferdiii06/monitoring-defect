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
        $middleware->alias([
            'admin'    => \App\Http\Middleware\EnsureIsAdmin::class,
            'operator' => \App\Http\Middleware\EnsureIsOperator::class,
            'rework'   => \App\Http\Middleware\EnsureIsRework::class,
        ]);
        $middleware->trimStrings(except: [
            fn (\Illuminate\Http\Request $request) => $request->is('master/kanban-format*'),
        ]);
        $middleware->convertEmptyStringsToNull(except: [
            fn (\Illuminate\Http\Request $request) => $request->is('master/kanban-format*'),
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
