<?php

use App\Classes\ModuleRouting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure( basePath: dirname( __DIR__ ) )
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        using: function () {
            Route::middleware( 'api' )
                ->prefix( 'api' )
                ->group( base_path( 'routes/api.php' ) );

            Route::middleware( 'web' )
                ->group( base_path( 'routes/web.php' ) );

            ModuleRouting::register();
        },
    )
    ->withSchedule( function ( Schedule $schedule ) {
        include_once __DIR__ . '/modules-schedule.php';
    } )
    ->withMiddleware( function ( Middleware $middleware ) {
        include_once __DIR__ . '/middleware.php';
    } )
    ->withExceptions( function ( Exceptions $exceptions ) {
        include_once __DIR__ . '/exceptions.php';
    } )->create();
