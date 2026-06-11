<?php

use App\Http\Controllers\Dashboard\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::prefix( 'marketplace' )->group( function() {
    Route::get( 'modules', [ MarketplaceController::class, 'getModules' ] );
    Route::get( 'licenses/{item}', [ MarketplaceController::class, 'getLicenses' ] );
    Route::post( 'add-to-cart', [ MarketplaceController::class, 'addToCart' ] );
    Route::post( 'download', [ MarketplaceController::class, 'downloadModule' ]);
    Route::get( 'categories', [ MarketplaceController::class, 'getCategories' ] );
});