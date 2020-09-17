<?php
// Route::get( 'reset', 'Dashboard\ResetController@truncateAllTables' );
Route::post( 'reset', 'Dashboard\ResetController@truncateWithDemo' );