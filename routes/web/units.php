<?php

use App\Http\Controllers\Dashboard\UnitsController;
use Illuminate\Support\Facades\Route;

Route::get( '/units', [ UnitsController::class, 'listUnits' ]);
Route::get( '/units/edit/{unit}', [ UnitsController::class, 'editUnit' ]);
Route::get( '/units/create', [ UnitsController::class, 'createUnit' ]);
Route::get( '/units/groups', [ UnitsController::class, 'listUnitsGroups' ]);
Route::get( '/units/groups/create', [ UnitsController::class, 'createUnitGroup' ]);
Route::get( '/units/groups/edit/{group}', [ UnitsController::class, 'editUnitGroup' ]);