<?php

use App\Http\Controllers\Dashboard\ProcurementController;
use Illuminate\Support\Facades\Route;

Route::get( '/procurements', [ ProcurementController::class, 'listProcurements' ])->name( ns()->routeName( 'ns.procurement-list' ) );
Route::get( '/procurements/create', [ ProcurementController::class, 'createProcurement' ])->name( ns()->routeName( 'ns.procurement-create' ) );
Route::get( '/procurements/products', [ ProcurementController::class, 'getProcurementProducts' ])->name( ns()->routeName( 'ns.procurement-products' ) );
Route::get( '/procurements/products/edit/{product}', [ ProcurementController::class, 'editProcurementProduct' ])->name( ns()->routeName( 'ns.procurement-edit-products' ) );
Route::get( '/procurements/edit/{procurement}', [ ProcurementController::class, 'updateProcurement' ])->name( ns()->routeName( 'ns.procurement-edit' ) );
Route::get( '/procurements/edit/{procurement}/invoice', [ ProcurementController::class, 'procurementInvoice' ])->name( ns()->routeName( 'ns.procurement-invoice' ) );