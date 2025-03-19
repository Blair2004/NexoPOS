<?php

use App\Http\Controllers\DriversController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::get( '/drivers', [ DriversController::class, 'listDrivers' ] )->name( 'ns.dashboard.drivers' )->middleware( [ NsRestrictMiddleware::arguments( 'nexopos.read.drivers' )] );
Route::get( '/drivers/create', [ DriversController::class, 'createDriver' ] )->name( 'ns.dashboard.drivers-create' )->middleware( [ NsRestrictMiddleware::arguments( 'nexopos.create.drivers' )] );
Route::get( '/drivers/edit/{driver}', [ DriversController::class, 'editDriver' ] )->name( 'ns.dashboard.drivers-edit' )->middleware( [ NsRestrictMiddleware::arguments( 'nexopos.update.drivers' )] );
Route::get( '/drivers/{driver}/orders', [ DriversController::class, 'getDriverOrders' ] )->name( 'ns.dashboard.drivers-orders-list' );