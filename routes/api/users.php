<?php

use Illuminate\Support\Facades\Route;

Route::get( '/users/roles', 'Dashboard\UserController@getRoles' );
Route::get( '/users/permissions', 'Dashboard\UserController@getPermissions' );