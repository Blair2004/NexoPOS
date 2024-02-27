<?php

use App\Http\Controllers\Dashboard\TaxesController;
use Illuminate\Support\Facades\Route;

Route::post( 'taxes', [ TaxesController::class, 'post' ] );
Route::put( 'taxes/{id}', [ TaxesController::class, 'put' ] );
Route::delete( 'taxes/{id}', [ TaxesController::class, 'delete' ] );

Route::get( 'taxes/{id?}', [ TaxesController::class, 'get' ] )->where( [ 'id' => '[0-9]+' ] );
Route::get( 'taxes/groups/{id?}', [ TaxesController::class, 'getTaxGroup' ] );
