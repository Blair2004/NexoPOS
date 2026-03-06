<?php

use App\Http\Controllers\Dashboard\ProcurementController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.read.procurements' ) )->group( function () {
    Route::get( 'procurements/{id?}', [ ProcurementController::class, 'list' ] )->where( 'id', '[0-9]+' );
    Route::get( 'procurements/{id}/products', [ ProcurementController::class, 'procurementProducts' ] );
    Route::get( 'procurements/{id}/refresh', [ ProcurementController::class, 'refreshProcurement' ] );
    Route::get( 'procurements/preload/{uuid}', [ ProcurementController::class, 'preload' ] );
    Route::get( 'procurements/low-stock-suggestions', [ ProcurementController::class, 'getLowStockSuggestions' ] );
} );

Route::get( 'procurements/{procurement}/set-as-paid', [ ProcurementController::class, 'setAsPaid' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.update.procurements' ) );

Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.create.procurements' ) )->group( function () {
    Route::post( 'procurements/preload', [ ProcurementController::class, 'storePreload' ] );
    Route::post( 'procurements/{id}/products', [ ProcurementController::class, 'procure' ] );
    Route::post( 'procurements', [ ProcurementController::class, 'create' ] );
    Route::post( 'procurements/products/search-procurement-product', [ ProcurementController::class, 'searchProcurementProduct' ] );
    Route::post( 'procurements/products/search-product', [ ProcurementController::class, 'searchProduct' ] );
} );

Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.update.procurements' ) )->group( function () {
    Route::put( 'procurements/{procurement}', [ ProcurementController::class, 'edit' ] );
    Route::put( 'procurements/{procurement}/change-payment-status', [ ProcurementController::class, 'changePaymentStatus' ] );
    Route::put( 'procurements/{id}/products/{product_id}', [ ProcurementController::class, 'editProduct' ] );
    Route::put( 'procurements/{id}/products', [ ProcurementController::class, 'bulkUpdateProducts' ] );
} );

Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.delete.procurements' ) )->group( function () {
    Route::delete( 'procurements/{id}/products/{product_id}', [ ProcurementController::class, 'deleteProcurementProduct' ] );
    Route::delete( 'procurements/{id}', [ ProcurementController::class, 'deleteProcurement' ] )->where( 'id', '[0-9]+' );
} );
