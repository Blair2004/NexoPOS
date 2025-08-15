<?php

use App\Http\Controllers\Dashboard\UsersController;
use Illuminate\Support\Facades\Route;

Route::get( '/user', [ UsersController::class, 'getUser' ] );
Route::get( '/user/permissions', [ UsersController::class, 'getUserPermissions' ] );
Route::post( '/user/access/{id}', [ UsersController::class, 'approveAccess' ] );
Route::get( '/user/access/{id}', [ UsersController::class, 'getAccess' ] );
Route::get( '/user/access/{access}/use', [ UsersController::class, 'markAccessAsUsed' ] );
Route::get( '/users/roles', [ UsersController::class, 'getRoles' ] );
Route::get( '/users', [ UsersController::class, 'getUsers' ] );
Route::put( '/users/roles', [ UsersController::class, 'updateRole' ] );
Route::get( '/users/roles/{role}/clone', [ UsersController::class, 'cloneRole' ] );
Route::get( '/users/permissions', [ UsersController::class, 'getPermissions' ] );
Route::post( '/users/widgets', [ UsersController::class, 'configureWidgets' ] );
Route::post( '/users/create-token', [ UsersController::class, 'createToken' ] );
Route::get( '/users/tokens', [ UsersController::class, 'getTokens' ] );
Route::delete( '/users/tokens/{id}', [ UsersController::class, 'deleteToken' ] );
Route::post( '/users/check-permission', [ UsersController::class, 'checkPermission' ] );
