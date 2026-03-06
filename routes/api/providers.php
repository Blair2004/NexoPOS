<?php

use App\Http\Controllers\Dashboard\ProvidersController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::get( 'providers', [ ProvidersController::class, 'list' ] )
    ->middleware( NsRestrictMiddleware::arguments( 'nexopos.read.providers' ) );

Route::get( 'providers/{id}/procurements', [ ProvidersController::class, 'providerProcurements' ] )
    ->middleware( NsRestrictMiddleware::arguments( 'nexopos.read.providers' ) );

Route::delete( 'providers/{id}', [ ProvidersController::class, 'deleteProvider' ] )
    ->middleware( NsRestrictMiddleware::arguments( 'nexopos.delete.providers' ) );
