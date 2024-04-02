<?php

use App\Http\Controllers\Dashboard\ReportsController;
use Illuminate\Support\Facades\Route;

Route::get( '/reports/sales', [ ReportsController::class, 'salesReport' ] )->name( ns()->routeName( 'ns.dashboard.report.sales' ) );
Route::get( '/reports/sales-progress', [ ReportsController::class, 'salesProgress' ] )->name( ns()->routeName( 'ns.dashboard.report.sale-progress' ) );
Route::get( '/reports/low-stock', [ ReportsController::class, 'stockReport' ] )->name( ns()->routeName( 'ns.dashboard.reports-low-stock' ) ); // @todo update
Route::get( '/reports/sold-stock', [ ReportsController::class, 'soldStock' ] )->name( ns()->routeName( 'ns.dashboard.reports.sold-stock' ) );
Route::get( '/reports/stock-history', [ ReportsController::class, 'stockCombinedReport' ] )->name( ns()->routeName( 'ns.dashboard.reports.stock-history' ) );
Route::get( '/reports/profit', [ ReportsController::class, 'profit' ] )->name( ns()->routeName( 'ns.dashboard.reports.profit' ) );
Route::get( '/reports/transactions', [ ReportsController::class, 'transactionsReport' ] )->name( ns()->routeName( 'ns.dashboard.reports.transactions' ) );
Route::get( '/reports/annual-report', [ ReportsController::class, 'annualReport' ] )->name( ns()->routeName( 'ns.dashboard.reports-annual' ) ); // @todo update
Route::get( '/reports/payment-types', [ ReportsController::class, 'salesByPaymentTypes' ] )->name( ns()->routeName( 'ns.dashboard.reports.payment-types' ) );
Route::get( '/reports/customers-statement', [ ReportsController::class, 'showCustomerStatement' ] )->name( ns()->routeName( 'ns.dashboard.reports.customers-statement' ) );
