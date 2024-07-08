<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\PasswordRecoveryMiddleware;
use App\Http\Middleware\RegistrationMiddleware;
use Illuminate\Support\Facades\Route;

Route::get( '/sign-in', [ AuthController::class, 'signIn' ] )->name( ns()->routeName( 'ns.login' ) );
Route::get( '/auth/activate/{user}/{token}', [ AuthController::class, 'activateAccount' ] )->name( ns()->routeName( 'ns.activate-account' ) );
Route::get( '/new-password/{user}/{token}', [ AuthController::class, 'newPassword' ] )->name( ns()->routeName( 'ns.new-password' ) );
Route::get( '/sign-out', [ AuthController::class, 'signOut' ] )->name( ns()->routeName( 'ns.logout' ) );
Route::post( '/auth/sign-in', [ AuthController::class, 'postSignIn' ] )->name( ns()->routeName( 'ns.login.post' ) );

/**
 * should protect access with
 * the registration is explictely disabled
 */
Route::middleware( [
    RegistrationMiddleware::class,
] )->group( function () {
    Route::post( '/auth/sign-up', [ AuthController::class, 'postSignUp' ] )->name( ns()->routeName( 'ns.register.post' ) );
    Route::get( '/sign-up', [ AuthController::class, 'signUp' ] )->name( ns()->routeName( 'ns.register' ) );
} );

/**
 * Should protect recovery when the
 * recovery is explicitly disabled
 */
Route::middleware( [
    PasswordRecoveryMiddleware::class,
] )->group( function () {
    Route::get( '/password-lost', [ AuthController::class, 'passwordLost' ] )->name( ns()->routeName( 'ns.password-lost' ) );
    Route::post( '/auth/password-lost', [ AuthController::class, 'postPasswordLost' ] )->name( ns()->routeName( 'ns.password-lost.post' ) );
    Route::post( '/auth/new-password/{user}/{token}', [ AuthController::class, 'postNewPassword' ] )->name( ns()->routeName( 'ns.post.new-password' ) );
} );
