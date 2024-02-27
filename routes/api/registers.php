<?php

use App\Http\Controllers\Dashboard\CashRegistersController;
use Illuminate\Support\Facades\Route;

Route::get( 'cash-registers/{id?}', [ CashRegistersController::class, 'getRegisters' ] )->where( [ 'id' => '[0-9]+' ] );
Route::get( 'cash-registers/used', [ CashRegistersController::class, 'getUsedRegister' ] );
Route::post( 'cash-registers/{action}/{register}', [ CashRegistersController::class, 'performAction' ] );
Route::get( 'cash-registers/session-history/{register}', [ CashRegistersController::class, 'getSessionHistory' ] );
// Route::get( 'registers/{id}/history', [ CashRegistersController::class, 'getHistory' ]);
// Route::get( 'registers/{id}/orders', [ CashRegistersController::class, 'getRegisterOrders' ]);
// Route::put( 'registers/{id}', [ CashRegistersController::class, 'editRegister' ]);
// Route::post( 'registers', [ CashRegistersController::class, 'create' ]);
// Route::delete( 'registers/{id}', [ CashRegistersController::class, 'deleteRegister' ]);
// Route::delete( 'registers/{id}/history/{history_id}', [ CashRegistersController::class, 'deleteRegisterHistory' ]);
