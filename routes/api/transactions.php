<?php

use App\Http\Controllers\Dashboard\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get( 'transactions/{id?}', [ TransactionController::class, 'get' ] )->where( 'id', '[0-9]+' );
Route::get( 'transactions/trigger/{transaction?}', [ TransactionController::class, 'triggerTransaction' ] )->where( 'id', '[0-9]+' );
Route::get( 'transactions/configurations/{transaction?}', [ TransactionController::class, 'getConfigurations' ] );
Route::get( 'transactions-accounts/reset-defaults', [ TransactionController::class, 'resetDefaultAccounts' ] )->name( ns()->routeName( 'ns.transactions-account.reset-defaults' ) );
Route::get( 'transactions-accounts/{id?}', [ TransactionController::class, 'getExpensesCategories' ] )->where( 'id', '[0-9]+' );
Route::get( 'transactions-accounts/sub-accounts', [ TransactionController::class, 'getSubAccounts' ] )->where( 'id', '[0-9]+' );
Route::get( 'transactions-accounts/actions', [ TransactionController::class, 'getActions' ] )->where( 'id', '[0-9]+' );
Route::get( 'transactions-accounts/{id}/history', [ TransactionController::class, 'getTransactionAccountsHistory' ] );
Route::post( 'transactions', [ TransactionController::class, 'post' ] );
Route::get( 'transactions/history/{history}/create-reflection', [ TransactionController::class, 'createReflection' ] )->where( 'id', '[0-9]+' );
Route::post( 'transactions/rules', [ TransactionController::class, 'saveRule' ] );
Route::get( 'transactions/rules', [ TransactionController::class, 'getRules' ] );
Route::post( 'transactions-accounts', [ TransactionController::class, 'postTransactionsAccount' ] );
Route::put( 'transactions/{id}', [ TransactionController::class, 'put' ] )->where( 'id', '[0-9]+' );
Route::put( 'transactions-accounts/{account}', [ TransactionController::class, 'putTransactionAccount' ] )->where( 'id', '[0-9]+' );
Route::delete( 'transactions/{transaction}', [ TransactionController::class, 'delete' ] )->where( 'transaction', '[0-9]+' );
Route::delete( 'transactions-accounts/{account}', [ TransactionController::class, 'deleteAccount' ] )->where( 'id', '[0-9]+' );
Route::post( 'transactions-accounts/category-identifier', [ TransactionController::class, 'getTransactionAccountFromCategory' ] )->name( ns()->routeName( 'ns.transactions-account.category-identifier' ) );
