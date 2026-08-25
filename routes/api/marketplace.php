<?php

use App\Http\Controllers\Dashboard\MarketplaceController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix( 'marketplace' )
    ->middleware( NsRestrictMiddleware::arguments( 'manage.modules' ) )
    ->group( function () {
        Route::get( 'modules', [ MarketplaceController::class, 'getModules' ] );
        Route::get( 'licenses/{item}', [ MarketplaceController::class, 'getLicenses' ] );
        Route::post( 'add-to-cart', [ MarketplaceController::class, 'addToCart' ] );
        Route::post( 'download', [ MarketplaceController::class, 'downloadModule' ] );
        Route::get( 'categories', [ MarketplaceController::class, 'getCategories' ] );
    } );
