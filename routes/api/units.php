<?php

use App\Http\Controllers\Dashboard\UnitsController;
use Illuminate\Support\Facades\Route;

Route::get( 'units/{id?}', [ UnitsController::class, 'get' ] );
Route::get( 'units/{id}/group', [ UnitsController::class, 'getUnitParentGroup' ] );
Route::get( 'units/{id}/siblings', [ UnitsController::class, 'getSiblingUnits' ] );
Route::get( 'units-groups/{id?}', [ UnitsController::class, 'getGroups' ] );

Route::post( 'units', [ UnitsController::class, 'postUnit' ] );
Route::post( 'units-groups', [ UnitsController::class, 'postGroup' ] );

Route::delete( 'units/{id}', [ UnitsController::class, 'deleteUnit' ] );
Route::delete( 'units-groups/{id}', [ UnitsController::class, 'deleteUnitGroup' ] );

Route::put( 'units-groups/{id}', [ UnitsController::class, 'putGroup' ] );
Route::put( 'units/{id}', [ UnitsController::class, 'putUnit' ] );

Route::get( 'units-groups/{id}/units', [ UnitsController::class, 'getGroupUnits' ] );
