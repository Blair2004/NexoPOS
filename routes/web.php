<?php

use App\Events\BeforeStartWebRouteEvent;
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

$domain = pathinfo( env( 'APP_URL' ) );

/**
 * If something has to happen
 * before the web routes are saved
 * this will be performmed here.
 */
BeforeStartWebRouteEvent::dispatch();

/**
 * By default, wildcard is disabled
 * on the system. In order to enable it, the user
 * will have to follow these instructions https://my.nexopos.com/en/documentation/wildcards
 */
if ( env( 'NS_WILDCARD_ENABLED' ) ) {
    /**
     * The defined route should only be applicable
     * to the main domain.
     */
    $domainString = ( $domain[ 'filename' ] ?: 'localhost' ) . ( isset( $domain[ 'extension' ] ) ? '.' . $domain[ 'extension' ] : '' );

    Route::domain( $domainString )->group( function () {
        include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'web-base.php';
    });
} else {
    include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'web-base.php';
}
