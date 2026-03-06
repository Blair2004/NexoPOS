<?php

use App\Http\Controllers\DashboardController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware( NsRestrictMiddleware::arguments( 'read.dashboard' ) )->group( function () {
    Route::get( 'dashboard/day', [ DashboardController::class, 'getCards' ] );
    Route::get( 'dashboard/best-customers', [ DashboardController::class, 'getBestCustomers' ] );
    Route::get( 'dashboard/best-cashiers', [ DashboardController::class, 'getBestCashiers' ] );
    Route::get( 'dashboard/recent-orders', [ DashboardController::class, 'getRecentsOrders' ] );
    Route::get( 'dashboard/weeks', [ DashboardController::class, 'getWeekReports' ] );
} );
