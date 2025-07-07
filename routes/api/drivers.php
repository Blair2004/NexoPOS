<?php

use App\Http\Controllers\DriversController;
use Illuminate\Support\Facades\Route;

Route::post( '/drivers/{driver}/status', [ DriversController::class, 'changeStatus' ]);
Route::get( '/drivers/{status}', [ DriversController::class, 'getDriverByStatus' ]);
Route::put( '/drivers/orders/{order}', [ DriversController::class, 'updateOrder' ]);
Route::post( '/drivers/orders/{order}/start', [ DriversController::class, 'startDelivery' ]);
Route::post( '/drivers/orders/{order}/reject', [ DriversController::class, 'rejectDelivery' ]);
Route::get( '/drivers', [ DriversController::class, 'getDrivers' ]);
Route::get('/drivers/{driver}/latest-deliveries', [ DriversController::class, 'latestDeliveries' ]);
Route::get('/drivers/earnings/stats', [ DriversController::class, 'getDriverEarningsStats' ]);