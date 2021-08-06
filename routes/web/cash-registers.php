<?php

use App\Classes\Hook;
use App\Http\Controllers\Dashboard\CashRegistersController;
use Illuminate\Support\Facades\Route;

Route::get( '/cash-registers', [ CashRegistersController::class, 'listRegisters' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.registers-list' ) );
Route::get( '/cash-registers/create', [ CashRegistersController::class, 'createRegister' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.registers-create' ) );
Route::get( '/cash-registers/edit/{register}', [ CashRegistersController::class, 'editRegister' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.registers-edit' ) );
Route::get( '/cash-registers/history/{register}', [ CashRegistersController::class, 'getRegisterHistory' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.registers-history' ) );