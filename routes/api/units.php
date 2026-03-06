<?php

use App\Http\Controllers\Dashboard\UnitsController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.read.products-units' ) )->group( function () {
    Route::get( 'units/{id?}', [ UnitsController::class, 'get' ] );
    Route::get( 'units/{id}/group', [ UnitsController::class, 'getUnitParentGroup' ] );
    Route::get( 'units/{id}/siblings', [ UnitsController::class, 'getSiblingUnits' ] );
    Route::get( 'units-groups/{id?}', [ UnitsController::class, 'getGroups' ] );
    Route::get( 'units-groups/{id}/units', [ UnitsController::class, 'getGroupUnits' ] );
} );

Route::post( 'units', [ UnitsController::class, 'postUnit' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.create.products-units' ) );
Route::post( 'units-groups', [ UnitsController::class, 'postGroup' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.create.products-units' ) );

Route::delete( 'units/{id}', [ UnitsController::class, 'deleteUnit' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.delete.products-units' ) );
Route::delete( 'units-groups/{id}', [ UnitsController::class, 'deleteUnitGroup' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.delete.products-units' ) );

Route::put( 'units-groups/{id}', [ UnitsController::class, 'putGroup' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.update.products-units' ) );
Route::put( 'units/{id}', [ UnitsController::class, 'putUnit' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.update.products-units' ) );
