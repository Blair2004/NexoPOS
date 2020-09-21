<?php

use Illuminate\Support\Facades\Route;

Route::get( 'medias', 'MediasController@getMedias' );
Route::delete( 'medias/{id}', 'MediasController@deleteMedia' );
Route::put( 'medias/{id}', 'MediasController@updateMedia' );
Route::post( 'medias/bulk-delete/', 'MediasController@bulkDeleteMedias' );