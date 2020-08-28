<?php

use Illuminate\Support\Facades\Route;

Route::get( '/forms/{identifier}', 'Dashboard\FormsController@getForm' );
Route::post( '/forms/{identifier}', 'Dashboard\FormsController@saveForm' );