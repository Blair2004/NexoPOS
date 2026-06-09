<?php

use App\Http\Controllers\Dashboard\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::prefix( 'marketplace' )->group( function() {
    Route::get( 'modules', [ MarketplaceController::class, 'getModules' ] );
});