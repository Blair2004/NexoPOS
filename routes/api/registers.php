<?php

use App\Http\Controllers\Dashboard\RegistersController;
use Illuminate\Support\Facades\Route;

Route::get( 'registers/{id?}', [ RegistersController::class, 'getRegister' ]);
Route::get( 'registers/{id}/history', [ RegistersController::class, 'getHistory' ]);
Route::get( 'registers/{id}/orders', [ RegistersController::class, 'getRegisterOrders' ]);
Route::put( 'registers/{id}', [ RegistersController::class, 'editRegister' ]);
Route::post( 'registers', [ RegistersController::class, 'create' ]);
Route::delete( 'registers/{id}', [ RegistersController::class, 'deleteRegister' ]);
Route::delete( 'registers/{id}/history/{history_id}', [ RegistersController::class, 'deleteRegisterHistory' ]);