<?php

use App\Http\Controllers\Dashboard\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get( 'transactions/{id?}', [ TransactionController::class, 'get' ] )->where( 'id', '[0-9]+' );
Route::get( 'transactions/trigger/{transaction?}', [ TransactionController::class, 'triggerTransaction' ] )->where( 'id', '[0-9]+' );
Route::get( 'transactions/configurations/{transaction?}', [ TransactionController::class, 'getConfigurations' ] );
Route::get( 'transactions-accounts/{id?}', [ TransactionController::class, 'getExpensesCategories' ] )->where( 'id', '[0-9]+' );
Route::get( 'transactions-accounts/{id}/history', [ TransactionController::class, 'getTransactionAccountsHistory' ] );
Route::post( 'transactions', [ TransactionController::class, 'post' ] );
Route::post( 'transactions-accounts', [ TransactionController::class, 'postTransactionsAccount' ] );
Route::put( 'transactions/{id}', [ TransactionController::class, 'put' ] )->where( 'id', '[0-9]+' );
Route::put( 'transactions-accounts/{account}', [ TransactionController::class, 'putTransactionAccount' ] )->where( 'id', '[0-9]+' );
Route::delete( 'transactions/{transaction}', [ TransactionController::class, 'delete' ] )->where( 'id', '[0-9]+' );
Route::delete( 'transactions-accounts/{account}', [ TransactionController::class, 'deleteAccount' ] )->where( 'id', '[0-9]+' );
