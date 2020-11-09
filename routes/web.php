<?php

use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\CustomersGroupsController;
use App\Http\Controllers\Dashboard\ProductsController;
use App\Http\Controllers\Dashboard\OrdersController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckMigrationStatus;
use App\Events\WebRoutesLoadedEvent;
use App\Http\Middleware\StoreDetectorMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([ 'ns.installed', CheckMigrationStatus::class ])->group( function() {
    Route::get( '/sign-in', 'AuthController@signIn' )->name( 'ns.login' );
    Route::get( '/sign-up', 'AuthController@signUp' )->name( 'ns.register' );
    Route::get( '/password-lost', 'AuthController@passwordLost' );
    Route::get( '/new-password', 'AuthController@newPassword' );

    Route::post( '/auth/sign-in', 'AuthController@postSignIn' );
    Route::post( '/auth/sign-up', 'AuthController@postSignUp' )->name( 'ns.register.post' );
    Route::get( '/sign-out', 'AuthController@signOut' )->name( 'ns.logout' );
    Route::get( '/database-update/', 'UpdateController@updateDatabase' )->withoutMiddleware([ CheckMigrationStatus::class ])
        ->name( 'ns.database-update' );

    Route::middleware([ 
        'auth',
        'ns.check-application-health',
    ])->group( function() {
        Route::prefix( 'dashboard' )->group( function() {

            require( dirname( __FILE__ ) . '/nexopos.php' );

            event( new WebRoutesLoadedEvent( 'dashboard' ) );
    
            Route::get( '/modules', 'Dashboard\ModulesController@listModules' )->name( 'ns.dashboard.modules.list' );
            Route::get( '/modules/upload', 'Dashboard\ModulesController@showUploadModule' )->name( 'ns.dashboard.modules.upload' );
            Route::get( '/modules/download/{identifier}', 'Dashboard\ModulesController@downloadModule' )->name( 'ns.dashboard.modules.upload' );
            Route::get( '/modules/migrate/{namespace}', 'Dashboard\ModulesController@migrateModule' )->name( 'ns.dashboard.modules.migrate' );
        });
    });

    include_once( dirname( __FILE__ ) . '/api/stores.php' );
});

Route::middleware([ 'ns.not-installed' ])->group( function() {
    Route::prefix( '/do-setup/' )->group( function() {
        Route::get( '', 'SetupController@welcome' )->name( 'setup' );
    });
});

Route::get( '/routes', function() {
    return ( array ) app( 'router' )->getRoutes();
});
