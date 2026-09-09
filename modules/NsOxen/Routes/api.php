<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\NsOxen\Http\Controllers\OxenAdminController;
use Modules\NsOxen\Http\Controllers\OxenController;

Route::prefix( 'oxen' )->middleware( ['auth', NsRestrictMiddleware::arguments( 'ns.oxen.use' )] )->group( function (): void {
    Route::get( 'csrf-token', [OxenController::class, 'csrfToken'] );
    Route::get( 'bootstrap', [OxenController::class, 'bootstrap'] )->name( 'ns-oxen.bootstrap' );
    Route::get( 'tools', [OxenController::class, 'tools'] )->name( 'ns-oxen.tools' );
    Route::get( 'conversations', [OxenController::class, 'index'] );
    Route::post( 'conversations', [OxenController::class, 'store'] );
    Route::get( 'conversations/{publicId}', [OxenController::class, 'show'] );
    Route::put( 'conversations/{publicId}', [OxenController::class, 'update'] );
    Route::delete( 'conversations/{publicId}', [OxenController::class, 'destroy'] );
    Route::post( 'conversations/{publicId}/messages', [OxenController::class, 'message'] )->middleware( 'throttle:oxen-assistant' );
    Route::put( 'launcher-preference', [OxenController::class, 'preference'] );
    Route::post( 'actions/{publicId}/execute', [OxenController::class, 'executeAction'] )->name( 'ns-oxen.actions.execute' );
    Route::post( 'actions/{publicId}/reject', [OxenController::class, 'rejectAction'] )->name( 'ns-oxen.actions.reject' );
    Route::post( 'actions/{publicId}/retry', [OxenController::class, 'retryAction'] )->name( 'ns-oxen.actions.retry' );
    Route::prefix( 'admin' )->middleware( NsRestrictMiddleware::arguments( 'ns.oxen.manage' ) )->group( function (): void {
        Route::get( 'settings', [OxenAdminController::class, 'show'] );
        Route::put( 'settings', [OxenAdminController::class, 'update'] );
        Route::post( 'connection-test', [OxenAdminController::class, 'test'] );
        Route::get( 'usage', [OxenAdminController::class, 'usage'] );
        Route::get( 'audit', [OxenAdminController::class, 'audit'] )->middleware( NsRestrictMiddleware::arguments( 'ns.oxen.view-audit' ) );
    } );
} );
