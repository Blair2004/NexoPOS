<?php

use App\Http\Controllers\BankingController;
use Illuminate\Support\Facades\Route;

Route::get( '/banking/cash-flow', [ BankingController::class, 'transactionsList'] )->name( ns()->routeName( 'ns.dashboard.banking.cash-flow' ) );
