<?php

use Illuminate\Support\Facades\Route;

Route::get( '/forms/{resource}/{identifier?}', 'Dashboard\FormsController@getForm' );
Route::post( '/forms/{resource}/{identifier?}', 'Dashboard\FormsController@saveForm' );