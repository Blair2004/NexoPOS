<?php

use App\Http\Controllers\BankingController;
use Illuminate\Support\Facades\Route;

Route::get( '/banking/cash-flow', [ BankingController::class, 'cashFlowList']);
Route::get( '/banking/cash-flow/create', [ BankingController::class, 'createCashFlow']);
Route::get( '/banking/cash-flow/edit/{cashFlow}', [ BankingController::class, 'editCashFlow']);