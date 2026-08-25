<?php

use App\Http\Controllers\Dashboard\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::prefix( 'oauth' )->group( function () {
    Route::prefix( 'mynexopos' )->group( function () {
        Route::get( 'authorize', [ MarketplaceController::class, 'oauthAuthorize' ] )->name( ns()->routeName( 'ns.oauth.mynexopos.authorize' ) );
        Route::get( 'callback', [ MarketplaceController::class, 'oauthCallback' ] )->name( ns()->routeName( 'ns.oauth.mynexopos.callback' ) );
    } );
} );
