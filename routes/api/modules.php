<?php

use App\Classes\Hook;
use Illuminate\Support\Facades\Route;

Route::get( 'modules/{argument?}', 'Dashboard\ModulesController@getModules' );
Route::put( 'modules/{argument}/disable', 'Dashboard\ModulesController@disableModule' );
Route::put( 'modules/{argument}/enable', 'Dashboard\ModulesController@enableModule' );
Route::post( 'modules/{identifier}/migrate', 'Dashboard\ModulesController@migrate' );
Route::delete( 'modules/{argument}/delete', 'Dashboard\ModulesController@deleteModule' );
Route::post( 'modules', 'Dashboard\ModulesController@uploadModule' )->name( Hook::filter( 'ns-route-name', 'ns.dashboard.modules.upload-post' ) );