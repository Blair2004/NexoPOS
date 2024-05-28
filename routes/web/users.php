<?php

use App\Http\Controllers\Dashboard\UsersController;
use Illuminate\Support\Facades\Route;

Route::get( '/users', [ UsersController::class, 'listUsers' ] )->name( 'ns.dashboard.users' );
Route::get( '/users/create', [ UsersController::class, 'createUser' ] )->name( 'ns.dashboard.users-create' );
Route::get( '/users/edit/{user}', [ UsersController::class, 'editUser' ] )->name( 'ns.dashboard.users.edit' );
Route::get( '/users/roles/permissions-manager', [ UsersController::class, 'permissionManager' ] );
Route::get( '/users/profile', [ UsersController::class, 'getProfile' ] )->name( 'ns.dashboard.users.profile' );
Route::get( '/users/roles', [ UsersController::class, 'rolesList' ] )->name( 'ns.dashboard.users.roles' );
Route::get( '/users/roles/create', [ UsersController::class, 'createRole' ] )->name( 'ns.dashboard.users.roles-create' );
Route::get( '/users/roles/edit/{role}', [ UsersController::class, 'editRole' ] )->name( 'ns.dashboard.users.roles-edit' );
