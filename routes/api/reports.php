<?php
// Route::get( 'reset', 'Dashboard\ResetController@truncateAllTables' );

use App\Http\Controllers\Dashboard\ReportsController;
use Illuminate\Support\Facades\Route;

Route::post( 'reports/sale-report', [ ReportsController::class, 'getSaleReport' ]);
Route::post( 'reports/sold-stock-report', [ ReportsController::class, 'getSoldStockReport' ]);
Route::post( 'reports/profit-report', [ ReportsController::class, 'getProfit' ]);
Route::post( 'reports/cash-flow', [ ReportsController::class, 'getCashFlow' ]);
Route::post( 'reports/annual-report', [ ReportsController::class, 'getAnnualReport' ]);
Route::post( 'reports/payment-types', [ ReportsController::class, 'getPaymentTypes' ]);
Route::post( 'reports/products-report', [ ReportsController::class, 'getProductsReport' ]);
Route::post( 'reports/compute/{type}', [ ReportsController::class, 'computeReport' ]);
Route::get( 'reports/cashier-report', [ ReportsController::class, 'getMyReport' ]);
Route::get( 'reports/low-stock', [ ReportsController::class, 'getLowStock' ]);