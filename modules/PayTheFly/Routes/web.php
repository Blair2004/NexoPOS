<?php

use Illuminate\Support\Facades\Route;
use Modules\PayTheFly\Http\Controllers\PayTheFlyController;

/**
 * PayTheFly web routes — all require authentication.
 */
Route::middleware([ 'ns.redirect' ])->prefix( 'dashboard/modules/paythefly' )->group( function () {
    // Settings page
    Route::get(
        'settings',
        [ PayTheFlyController::class, 'settings' ]
    )->name( 'ns.dashboard.modules-settings.paythefly' );

    Route::post(
        'settings',
        [ PayTheFlyController::class, 'saveSettings' ]
    )->name( 'ns.dashboard.modules-settings.paythefly.save' );

    // Redirect to PayTheFly payment page
    Route::get(
        'pay/{order}',
        [ PayTheFlyController::class, 'pay' ]
    )->name( 'paythefly.pay' );
});
