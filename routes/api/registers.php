<?php

use App\Http\Controllers\Dashboard\CashRegistersController;
use App\Http\Controllers\Dashboard\RegistersController;
use Illuminate\Support\Facades\Route;

Route::get( 'cash-registers/{id?}', [ CashRegistersController::class, 'getRegisters' ]);
// Route::get( 'registers/{id}/history', [ CashRegistersController::class, 'getHistory' ]);
// Route::get( 'registers/{id}/orders', [ CashRegistersController::class, 'getRegisterOrders' ]);
// Route::put( 'registers/{id}', [ CashRegistersController::class, 'editRegister' ]);
// Route::post( 'registers', [ CashRegistersController::class, 'create' ]);
// Route::delete( 'registers/{id}', [ CashRegistersController::class, 'deleteRegister' ]);
// Route::delete( 'registers/{id}/history/{history_id}', [ CashRegistersController::class, 'deleteRegisterHistory' ]);