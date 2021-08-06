<?php

use App\Http\Controllers\Dashboard\ProcurementController;
use Illuminate\Support\Facades\Route;

Route::get( '/procurements', [ ProcurementController::class, 'listProcurements' ]);
Route::get( '/procurements/create', [ ProcurementController::class, 'createProcurement' ]);
Route::get( '/procurements/products', [ ProcurementController::class, 'getProcurementProducts' ]);
Route::get( '/procurements/products/edit/{product}', [ ProcurementController::class, 'editProcurementProduct' ]);
Route::get( '/procurements/edit/{procurement}', [ ProcurementController::class, 'updateProcurement' ]);
Route::get( '/procurements/edit/{procurement}/invoice', [ ProcurementController::class, 'procurementInvoice' ]);