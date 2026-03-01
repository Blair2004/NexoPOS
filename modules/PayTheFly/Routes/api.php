<?php

use Illuminate\Support\Facades\Route;
use Modules\PayTheFly\Http\Controllers\PayTheFlyController;

/**
 * PayTheFly API routes.
 *
 * The webhook endpoint is public (no auth middleware) because PayTheFly
 * servers call it directly. HMAC verification happens inside the controller.
 */
Route::post(
    'paythefly/webhook',
    [ PayTheFlyController::class, 'webhook' ]
)->name( 'paythefly.webhook' );

/**
 * Authenticated API routes — require a valid NexoPOS session or API token.
 */
Route::middleware([ 'auth:sanctum' ])->prefix( 'paythefly' )->group( function () {
    // Get the payment URL for an order (used by POS frontend)
    Route::get(
        'orders/{order}/payment-url',
        [ PayTheFlyController::class, 'paymentUrl' ]
    )->name( 'paythefly.payment-url' );

    // Poll payment status for an order
    Route::get(
        'orders/{order}/status',
        [ PayTheFlyController::class, 'status' ]
    )->name( 'paythefly.status' );
});
