<?php

use App\Events\WebRoutesLoadedEvent;
use App\Http\Controllers\Dashboard\CrudController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UpdateController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckApplicationHealthMiddleware;
use App\Http\Middleware\CheckMigrationStatus;
use App\Http\Middleware\ClearRequestCacheMiddleware;
use App\Http\Middleware\FooterOutputHookMiddleware;
use App\Http\Middleware\HandleCommonRoutesMiddleware;
use App\Http\Middleware\InstalledStateMiddleware;
use App\Http\Middleware\NotInstalledStateMiddleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::middleware( [ 'web' ] )->group( function () {
    Route::get( '/', [ HomeController::class, 'welcome' ] )->name( 'ns.welcome' );
} );

require dirname( __FILE__ ) . '/intermediate.php';

Route::middleware( [
    InstalledStateMiddleware::class,
    CheckMigrationStatus::class,
    SubstituteBindings::class,
] )->group( function () {
    /**
     * We would like to isolate certain routes as it's registered
     * for authentication and are likely to be applicable to sub stores
     */
    require dirname( __FILE__ ) . '/authenticate.php';

    Route::get( '/database-update', [ UpdateController::class, 'updateDatabase' ] )
        ->withoutMiddleware( [ CheckMigrationStatus::class ] )
        ->name( 'ns.database-update' );

    Route::middleware( [
        Authenticate::class,
        CheckApplicationHealthMiddleware::class,
        ClearRequestCacheMiddleware::class,
        FooterOutputHookMiddleware::class,
    ] )->group( function () {
        Route::prefix( 'dashboard' )->group( function () {
            event( new WebRoutesLoadedEvent( 'dashboard' ) );

            Route::middleware( [
                HandleCommonRoutesMiddleware::class,
            ] )->group( function () {
                require dirname( __FILE__ ) . '/nexopos.php';
            } );

            include dirname( __FILE__ ) . '/web/modules.php';
            include dirname( __FILE__ ) . '/web/users.php';

            Route::get( '/crud/download/{hash}', [ CrudController::class, 'downloadSavedFile' ] )->name( 'ns.dashboard.crud-download' );
        } );
    } );
} );

Route::middleware( [
    NotInstalledStateMiddleware::class,
    ClearRequestCacheMiddleware::class,
] )->group( function () {
    Route::prefix( '/do-setup/' )->group( function () {
        Route::get( '', [ SetupController::class, 'welcome' ] )->name( 'ns.do-setup' );
    } );
} );
