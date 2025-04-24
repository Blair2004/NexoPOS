<?php

use App\Http\Controllers\DriversController;
use Illuminate\Support\Facades\Route;

Route::post( '/drivers/{driver}/status', [ DriversController::class, 'changeStatus' ]);
Route::get( '/drivers/{status}', [ DriversController::class, 'getDriverByStatus' ]);
Route::put( '/drivers/orders/{order}', [ DriversController::class, 'updateOrder' ]);
Route::get( '/drivers', [ DriversController::class, 'getDrivers' ]);