<?php

use Illuminate\Support\Facades\Route;

Route::get( '/users/roles', 'Dashboard\UsersController@getRoles' );
Route::put( '/users/roles', 'Dashboard\UsersController@updateRole' );
Route::get( '/users/permissions', 'Dashboard\UsersController@getPermissions' );