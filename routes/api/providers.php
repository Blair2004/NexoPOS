<?php

use App\Http\Controllers\Dashboard\ProvidersController;
use Illuminate\Support\Facades\Route;

Route::get( 'providers', [ ProvidersController::class, 'list' ] );
Route::post( 'providers', [ ProvidersController::class, 'create' ] );
Route::put( 'providers/{id}', [ ProvidersController::class, 'edit' ] );
Route::get( 'providers/{id}/procurements', [ ProvidersController::class, 'providerProcurements' ] );
Route::get( 'providers/{id}', [ ProvidersController::class, 'getSingleProvider' ] );
Route::delete( 'providers/{id}', [ ProvidersController::class, 'deleteProvider' ] );
