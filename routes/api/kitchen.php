<?php

/**
 * Kitchen Display System (KDS) — MVP API.
 * Read access reuses `nexopos.read.orders`; marking a dish done
 * reuses `nexopos.update.orders` (no new permission in the MVP).
 */

use App\Http\Controllers\Dashboard\KitchenController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.read.orders' ) )->group( function () {
    Route::get( 'kitchen/orders', [ KitchenController::class, 'getKitchenOrders' ] );
} );

Route::post( 'kitchen/order-products/{item}/status', [ KitchenController::class, 'updateItemStatus' ] )
    ->where( 'item', '[0-9]+' )
    ->middleware( NsRestrictMiddleware::arguments( 'nexopos.update.orders' ) );
