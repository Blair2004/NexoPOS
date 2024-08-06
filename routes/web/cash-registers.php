<?php

use App\Http\Controllers\Dashboard\CashRegistersController;
use Illuminate\Support\Facades\Route;

Route::get( '/cash-registers', [ CashRegistersController::class, 'listRegisters' ] )->name( ns()->routeName( 'ns.dashboard.registers-list' ) ); // @todo update
Route::get( '/cash-registers/create', [ CashRegistersController::class, 'createRegister' ] )->name( ns()->routeName( 'ns.dashboard.registers-create' ) );
Route::get( '/cash-registers/edit/{register}', [ CashRegistersController::class, 'editRegister' ] )->name( ns()->routeName( 'ns.dashboard.registers-edit' ) );
Route::get( '/cash-registers/history/{register}', [ CashRegistersController::class, 'getRegisterHistory' ] )->name( ns()->routeName( 'ns.dashboard.registers-history' ) );
Route::get( '/cash-registers/z-report/{register}', [ CashRegistersController::class, 'getRegisterZReport' ] )->name( ns()->routeName( 'ns.dashboard.registers-zreport' ) );
