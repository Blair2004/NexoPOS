<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule( function( Schedule $schedule ) {
        include_once( __DIR__ . '/modules-schedule.php' );
    })
    ->withMiddleware(function (Middleware $middleware) {
        include_once( __DIR__ . '/middleware.php' );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        include_once( __DIR__ . '/exceptions.php' );
    })->create();