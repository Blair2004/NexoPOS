<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get( '/sign-in', [ AuthController::class, 'signIn' ])->name( 'ns.login' );
Route::get( '/auth/activate/{user}/{token}', [ AuthController::class, 'activateAccount' ])->name( 'ns.activate-account' );
Route::get( '/sign-up', [ AuthController::class, 'signUp' ])->name( 'ns.register' );
Route::get( '/password-lost', [ AuthController::class, 'passwordLost' ])->name( 'ns.password-lost' );
Route::get( '/new-password/{user}/{token}', [ AuthController::class, 'newPassword' ])->name( 'ns.new-password' );

Route::post( '/auth/sign-in', [ AuthController::class, 'postSignIn' ])->name( 'ns.login.post' );
Route::post( '/auth/sign-up', [ AuthController::class, 'postSignUp' ])->name( 'ns.register.post' );
Route::post( '/auth/password-lost', [ AuthController::class, 'postPasswordLost' ])->name( 'ns.password-lost' );
Route::post( '/auth/new-password/{user}/{token}', [ AuthController::class, 'postNewPassword' ])->name( 'ns.post.new-password' );
Route::get( '/sign-out', [ AuthController::class, 'signOut' ])->name( 'ns.logout' );