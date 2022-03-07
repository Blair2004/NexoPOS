<?php

use App\Http\Controllers\Dashboard\ProvidersController;
use Illuminate\Support\Facades\Route;

Route::get( '/providers', [ ProvidersController::class, 'listProviders' ]);
Route::get( '/providers/create', [ ProvidersController::class, 'createProvider' ]);
Route::get( '/providers/edit/{provider}', [ ProvidersController::class, 'editProvider' ]);
Route::get( '/providers/{provider}/procurements', [ ProvidersController::class, 'listProvidersProcurements' ]);
Route::get( '/providers/{provider}/products', [ ProvidersController::class, 'listProvidersProducts' ]);