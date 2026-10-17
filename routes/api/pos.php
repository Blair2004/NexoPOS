<?php

use App\Http\Controllers\Dashboard\OrdersController;
use App\Http\Controllers\Dashboard\PosCatalogController;
use App\Http\Middleware\NsRestrictAnyMiddleware;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix( 'pos' )->group( function (): void {
    Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.pos.read-products' ) )->group( function (): void {
        Route::get( 'catalog/categories/{category?}', [ PosCatalogController::class, 'categories' ] )->whereNumber( 'category' );
        Route::get( 'catalog/products/pinned', [ PosCatalogController::class, 'pinned' ] );
        Route::post( 'catalog/products/search', [ PosCatalogController::class, 'search' ] );
        Route::get( 'catalog/products/barcode/{reference}', [ PosCatalogController::class, 'barcode' ] );
        Route::get( 'catalog/products/{product}/units', [ PosCatalogController::class, 'units' ] )->whereNumber( 'product' );
    } );

    Route::middleware( NsRestrictAnyMiddleware::class . ':nexopos.read.orders,nexopos.pos.read-all-orders,nexopos.pos.read-own-orders' )
        ->group( function (): void {
            Route::get( 'orders/{order}', [ OrdersController::class, 'posOrder' ] )->whereNumber( 'order' );
            Route::get( 'orders/{order}/products', [ OrdersController::class, 'posOrderProducts' ] )->whereNumber( 'order' );
        } );
} );
