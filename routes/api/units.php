<?php
Route::get( 'units/{id?}', 'UnitsController@get' );
Route::get( 'units/{id}/group', 'UnitsController@getUnitParentGroup' );
Route::get( 'units-groups/{id?}', 'UnitsController@getGroups' );

Route::post( 'units', 'UnitsController@postUnit' );
Route::post( 'units-groups', 'UnitsController@postGroup' );

Route::delete( 'units/{id}', 'UnitsController@deleteUnit' );
Route::delete( 'units-groups/{id}', 'UnitsController@deleteUnitGroup' );


Route::put( 'units-groups/{id}', 'UnitsController@putGroup' );
Route::put( 'units/{id}', 'UnitsController@putUnit' );

Route::get( 'units-groups/{id}/units', 'UnitsController@getGroupUnits' );