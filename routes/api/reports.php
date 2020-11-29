<?php
// Route::get( 'reset', 'Dashboard\ResetController@truncateAllTables' );

use App\Http\Controllers\Dashboard\ReportsController;
use Illuminate\Support\Facades\Route;

Route::post( 'reports/sale-report', [ ReportsController::class, 'getSaleReport' ]);
Route::post( 'reports/sold-stock-report', [ ReportsController::class, 'getSoldStockReport' ]);
Route::post( 'reports/profit-report', [ ReportsController::class, 'getProfit' ]);