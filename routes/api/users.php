<?php

use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

// Current-user routes — accessible to any authenticated user
Route::get( '/user', [ UsersController::class, 'getUser' ] );
Route::get( '/user/permissions', [ UsersController::class, 'getUserPermissions' ] );
Route::post( '/user/access/{id}', [ UsersController::class, 'approveAccess' ] );
Route::get( '/user/access/{id}', [ UsersController::class, 'getAccess' ] );
Route::get( '/user/access/{access}/use', [ UsersController::class, 'markAccessAsUsed' ] );
Route::post( '/users/widgets', [ UsersController::class, 'configureWidgets' ] );
Route::post( '/users/create-token', [ UsersController::class, 'createToken' ] );
Route::get( '/users/tokens', [ UsersController::class, 'getTokens' ] );
Route::delete( '/users/tokens/{id}', [ UsersController::class, 'deleteToken' ] );
Route::post( '/users/check-permission', [ UsersController::class, 'checkPermission' ] );

// User management routes — restricted to privileged users
Route::get( '/users', [ UsersController::class, 'getUsers' ] )->middleware( NsRestrictMiddleware::arguments( 'read.users' ) );
Route::get( '/users/permissions', [ UsersController::class, 'getPermissions' ] )->middleware( NsRestrictMiddleware::arguments( 'read.users' ) );

// Role management routes
Route::get( '/users/roles', [ UsersController::class, 'getRoles' ] )->middleware( NsRestrictMiddleware::arguments( 'read.roles' ) );
Route::put( '/users/roles', [ UsersController::class, 'updateRole' ] )->middleware( NsRestrictMiddleware::arguments( 'update.roles' ) );
Route::get( '/users/roles/{role}/clone', [ UsersController::class, 'cloneRole' ] )->middleware( NsRestrictMiddleware::arguments( 'create.roles' ) );
