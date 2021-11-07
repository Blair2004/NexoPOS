<?php

use App\Events\BeforeStartApiRouteEvent;
use App\Http\Middleware\InstalledStateMiddleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

$domain     =   pathinfo( env( 'APP_URL' ) );

/**
 * If something has to happen
 * before the web routes are saved
 * this will be performmed here.
 */
BeforeStartApiRouteEvent::dispatch();

/**
 * The defined route should only be applicable
 * to the main domain.
 */
Route::domain( $domain[ 'filename' ] . '.' . $domain[ 'extension' ] )->group( function() {
    Route::prefix( 'nexopos/v4' )->group( function() {

        Route::middleware([ 
            InstalledStateMiddleware::class, 
            SubstituteBindings::class,
        ])->group( function() {

            include( dirname( __FILE__ ) . '/api/fields.php' );

            Route::middleware([
                'auth:sanctum', 
            ])->group( function() {
                include( dirname( __FILE__ ) . '/api/dashboard.php' );    
                include( dirname( __FILE__ ) . '/api/categories.php' );    
                include( dirname( __FILE__ ) . '/api/customers.php' );
                include( dirname( __FILE__ ) . '/api/expenses.php' );
                include( dirname( __FILE__ ) . '/api/modules.php' );
                include( dirname( __FILE__ ) . '/api/medias.php' );
                include( dirname( __FILE__ ) . '/api/notifications.php' );
                include( dirname( __FILE__ ) . '/api/orders.php' );
                include( dirname( __FILE__ ) . '/api/procurements.php' );
                include( dirname( __FILE__ ) . '/api/products.php' );
                include( dirname( __FILE__ ) . '/api/providers.php' );
                include( dirname( __FILE__ ) . '/api/registers.php' );
                include( dirname( __FILE__ ) . '/api/reset.php' );
                include( dirname( __FILE__ ) . '/api/reports.php' );
                include( dirname( __FILE__ ) . '/api/settings.php' );
                include( dirname( __FILE__ ) . '/api/rewards.php' );
                include( dirname( __FILE__ ) . '/api/transfer.php' );
                include( dirname( __FILE__ ) . '/api/taxes.php' );
                include( dirname( __FILE__ ) . '/api/crud.php' );
                include( dirname( __FILE__ ) . '/api/forms.php' );
                include( dirname( __FILE__ ) . '/api/units.php' );
                include( dirname( __FILE__ ) . '/api/users.php' );
            });
        });

        include( dirname( __FILE__ ) . '/api/hard-reset.php' );
        include_once( dirname( __FILE__ ) . '/api/update.php' );

        Route::prefix( 'setup' )->group( function() {
            Route::post( 'database', 'SetupController@checkDatabase' );
            Route::get( 'database', 'SetupController@checkDbConfigDefined' );
            Route::post( 'configuration', 'SetupController@saveConfiguration' );
        });
    });
});