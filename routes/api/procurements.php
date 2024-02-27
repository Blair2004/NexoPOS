<?php

use App\Http\Controllers\Dashboard\ProcurementController;
use Illuminate\Support\Facades\Route;

Route::get( 'procurements/{id?}', [ ProcurementController::class, 'list' ] )->where( 'id', '[0-9]+' );
Route::get( 'procurements/{id}/products', [ ProcurementController::class, 'procurementProducts' ] );
Route::get( 'procurements/{id}/reset', [ ProcurementController::class, 'resetProcurement' ] );
Route::get( 'procurements/{id}/refresh', [ ProcurementController::class, 'refreshProcurement' ] );
Route::get( 'procurements/{procurement}/set-as-paid', [ ProcurementController::class, 'setAsPaid' ] );

Route::post( 'procurements/{id}/products', [ ProcurementController::class, 'procure' ] );
Route::post( 'procurements', [ ProcurementController::class, 'create' ] );
Route::post( 'procurements/products/search-procurement-product', [ ProcurementController::class, 'searchProcurementProduct' ] );
Route::post( 'procurements/products/search-product', [ ProcurementController::class, 'searchProduct' ] );

Route::put( 'procurements/{procurement}', [ ProcurementController::class, 'edit' ] );
Route::put( 'procurements/{procurement}/change-payment-status', [ ProcurementController::class, 'changePaymentStatus' ] );
Route::put( 'procurements/{id}/products/{product_id}', [ ProcurementController::class, 'editProduct' ] );
Route::put( 'procurements/{id}/products', [ ProcurementController::class, 'bulkUpdateProducts' ] );

Route::delete( 'procurements/{id}/products/{product_id}', [ ProcurementController::class, 'deleteProcurementProduct' ] );
Route::delete( 'procurements/{id}', [ ProcurementController::class, 'deleteProcurement' ] )->where( 'id', '[0-9]+' );
