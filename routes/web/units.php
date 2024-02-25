<?php

use App\Http\Controllers\Dashboard\UnitsController;
use Illuminate\Support\Facades\Route;

Route::get( '/units', [ UnitsController::class, 'listUnits' ] )->name( ns()->routeName( 'ns.dashboard.units' ) );
Route::get( '/units/edit/{unit}', [ UnitsController::class, 'editUnit' ] )->name( ns()->routeName( 'ns.dashboard.units.edit' ) );
Route::get( '/units/create', [ UnitsController::class, 'createUnit' ] )->name( ns()->routeName( 'ns.dashboard.units.create' ) );
Route::get( '/units/groups', [ UnitsController::class, 'listUnitsGroups' ] )->name( ns()->routeName( 'ns.dashboard.units.groups' ) );
Route::get( '/units/groups/create', [ UnitsController::class, 'createUnitGroup' ] )->name( ns()->routeName( 'ns.dashboard.units.groups.create' ) );
Route::get( '/units/groups/edit/{group}', [ UnitsController::class, 'editUnitGroup' ] )->name( ns()->routeName( 'ns.dashboard.units.groups.edit' ) );
