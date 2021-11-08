<?php

use App\Events\BeforeStartWebRouteEvent;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\CheckMigrationStatus;
use App\Events\WebRoutesLoadedEvent;
use App\Http\Controllers\Dashboard\CrudController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\ModulesController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UpdateController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckApplicationHealthMiddleware;
use App\Http\Middleware\HandleCommonRoutesMiddleware;
use App\Http\Middleware\InstalledStateMiddleware;
use App\Http\Middleware\NotInstalledStateMiddleware;
use Illuminate\Support\Facades\Route;
use dekor\ArrayToTextTable;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Route as RoutingRoute;

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

$domain     =   pathinfo( env( 'APP_URL' ) );

/**
 * If something has to happen
 * before the web routes are saved
 * this will be performmed here.
 */
BeforeStartWebRouteEvent::dispatch();

/**
 * The defined route should only be applicable
 * to the main domain.
 */
$domainString   =   ( $domain[ 'filename' ] ?: 'localhost' ) . ( isset( $domain[ 'extension' ] ) ? '.' . $domain[ 'extension' ] : '' );

/**
 * By default, wildcard is disabled
 * on the system. In order to enable it, the user
 * will have to follow these instructions https://my.nexopos.com/en/documentation/wildcards
 */
if ( env( 'NS_WILDCARD_ENABLED' ) ) {
    Route::domain( $domainString )->group( function() {
        include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'web-base.php' );
    });
} else {
    include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'web-base.php' );
}


