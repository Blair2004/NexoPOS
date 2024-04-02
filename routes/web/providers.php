<?php

use App\Http\Controllers\Dashboard\ProvidersController;
use Illuminate\Support\Facades\Route;

Route::get( '/providers', [ ProvidersController::class, 'listProviders' ] )->name( ns()->routeName( 'ns.dashboard.providers' ) );
Route::get( '/providers/create', [ ProvidersController::class, 'createProvider' ] )->name( ns()->routeName( 'ns.dashboard.providers.create' ) );
Route::get( '/providers/edit/{provider}', [ ProvidersController::class, 'editProvider' ] )->name( ns()->routeName( 'ns.dashboard.providers.edit' ) );
Route::get( '/providers/{provider}/procurements', [ ProvidersController::class, 'listProvidersProcurements' ] )->name( ns()->routeName( 'ns.dashboard.providers.procurements' ) );
Route::get( '/providers/{provider}/products', [ ProvidersController::class, 'listProvidersProducts' ] )->name( ns()->routeName( 'ns.dashboard.providers.products' ) );
