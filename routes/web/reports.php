<?php

use App\Http\Controllers\Dashboard\ReportsController;
use Illuminate\Support\Facades\Route;

Route::get( '/reports/sales', [ ReportsController::class, 'salesReport' ]);
Route::get( '/reports/sales-progress', [ ReportsController::class, 'salesProgress' ]);

/**
 * @deprecated
 */
Route::get( '/reports/products-report', [ ReportsController::class, 'salesProgress' ]);

Route::get( '/reports/low-stock', [ ReportsController::class, 'lowStockReport' ])->name( ns()->routeName( 'ns.dashboard.reports-low-stock' ) );
Route::get( '/reports/sold-stock', [ ReportsController::class, 'soldStock' ]);
Route::get( '/reports/profit', [ ReportsController::class, 'profit' ]);
Route::get( '/reports/cash-flow', [ ReportsController::class, 'cashFlow' ]);
Route::get( '/reports/annual-report', [ ReportsController::class, 'annualReport' ])->name( ns()->routeName( 'ns.dashboard.reports-annual' ) );
Route::get( '/reports/payment-types', [ ReportsController::class, 'salesByPaymentTypes' ]);