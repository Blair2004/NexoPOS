<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\CheckMigrationStatus;
use App\Events\WebRoutesLoadedEvent;
use App\Http\Controllers\Dashboard\CrudController;
use App\Http\Controllers\Dashboard\ModulesController;
use App\Http\Controllers\Dashboard\UsersController;
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

/**
 * Exclusively, we'll check if the multistore module is available
 * and then load priorily his subdomain routes.
 * @since 4.6.0
 */
$filePath   =   dirname( __FILE__ ) . '/../modules/NsMultiStore/Routes/web-subdomains.php';

if( is_file( $filePath ) ) {
    include_once( $filePath );
}

Route::get('/', function () {
    return view('welcome', [
        'title'     =>  __( 'Welcome &mdash; NexoPOS 4.x' )
    ]);
})->middleware([
    'web',
]);

require( dirname( __FILE__ ) . '/intermediate.php' );

Route::middleware([ 
    InstalledStateMiddleware::class, 
    CheckMigrationStatus::class, 
    SubstituteBindings::class 
])->group( function() {
    /**
     * We would like to isolate certain routes as it's registered
     * for authentication and are likely to be applicable to sub stores
     */
    require( dirname( __FILE__ ) . '/authenticate.php' );

    Route::get( '/database-update', 'UpdateController@updateDatabase' )
        ->withoutMiddleware([ CheckMigrationStatus::class ])
        ->name( 'ns.database-update' );

    Route::middleware([ 
        Authenticate::class,
        CheckApplicationHealthMiddleware::class,
    ])->group( function() {

        Route::prefix( 'dashboard' )->group( function() {

            event( new WebRoutesLoadedEvent( 'dashboard' ) );

            Route::middleware([
                HandleCommonRoutesMiddleware::class
            ])->group( function() {
                require( dirname( __FILE__ ) . '/nexopos.php' );
            });

            include( dirname( __FILE__ ) . '/web/modules.php' );
            include( dirname( __FILE__ ) . '/web/users.php' );

            Route::get( '/crud/download/{hash}', [ CrudController::class, 'downloadSavedFile' ])->name( 'ns.dashboard.crud-download' );
        });
        
    });
});

Route::middleware([ NotInstalledStateMiddleware::class ])->group( function() {
    Route::prefix( '/do-setup/' )->group( function() {
        Route::get( '', 'SetupController@welcome' )->name( 'ns.do-setup' );
    });
});

if ( env( 'APP_DEBUG' ) ) {
    Route::get( '/routes', function() {
        $values     =   collect( array_values( ( array ) app( 'router' )->getRoutes() )[1] )->map( function( RoutingRoute $route ) {
            return [
                'uri'       =>  $route->uri(),
                'methods'   =>  collect( $route->methods() )->join( ', ' ),
                'name'      =>  $route->getName()
            ];
        })->values();
    
        return ( new ArrayToTextTable( $values->toArray() ) )->render();
    });
}
