<?php

use App\Http\Controllers\Dashboard\ReportsController;
use Illuminate\Support\Facades\Route;

Route::post( 'reports/sale-report', [ ReportsController::class, 'getSaleReport' ] );
Route::post( 'reports/sold-stock-report', [ ReportsController::class, 'getSoldStockReport' ] );
Route::post( 'reports/profit-report', [ ReportsController::class, 'getProfit' ] );
Route::post( 'reports/transactions', [ ReportsController::class, 'getAccountSummaryReport' ] );
Route::post( 'reports/annual-report', [ ReportsController::class, 'getAnnualReport' ] );
Route::post( 'reports/payment-types', [ ReportsController::class, 'getPaymentTypes' ] );
Route::post( 'reports/products-report', [ ReportsController::class, 'getProductsReport' ] );
Route::post( 'reports/compute/{type}', [ ReportsController::class, 'computeReport' ] );
Route::get( 'reports/cashier-report', [ ReportsController::class, 'getMyReport' ] );
Route::post( 'reports/low-stock', [ ReportsController::class, 'getLowStock' ] );
Route::post( 'reports/stock-report', [ ReportsController::class, 'getStockReport' ] );
Route::post( 'reports/product-history-combined', [ ReportsController::class, 'getProductHistoryCombined' ] );
Route::post( 'reports/customers-statement/{customer}', [ ReportsController::class, 'getCustomerStatement' ] );
Route::post( 'reports/compute-combined-report', [ ReportsController::class, 'computeCombinedReport' ] );
