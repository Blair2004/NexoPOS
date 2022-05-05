<?php

use App\Http\Controllers\BankingController;
use Illuminate\Support\Facades\Route;

Route::get( '/banking/cash-flow', [ BankingController::class, 'cashFlowList']);