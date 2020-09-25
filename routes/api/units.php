<?php

use Illuminate\Support\Facades\Route;

Route::get( 'units/{id?}', 'Dashboard\UnitsController@get' );
Route::get( 'units/{id}/group', 'Dashboard\UnitsController@getUnitParentGroup' );
Route::get( 'units-groups/{id?}', 'Dashboard\UnitsController@getGroups' );

Route::post( 'units', 'Dashboard\UnitsController@postUnit' );
Route::post( 'units-groups', 'Dashboard\UnitsController@postGroup' );

Route::delete( 'units/{id}', 'Dashboard\UnitsController@deleteUnit' );
Route::delete( 'units-groups/{id}', 'Dashboard\UnitsController@deleteUnitGroup' );


Route::put( 'units-groups/{id}', 'Dashboard\UnitsController@putGroup' );
Route::put( 'units/{id}', 'Dashboard\UnitsController@putUnit' );

Route::get( 'units-groups/{id}/units', 'Dashboard\UnitsController@getGroupUnits' );