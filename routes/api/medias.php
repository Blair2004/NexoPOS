<?php

use Illuminate\Support\Facades\Route;

Route::get( 'medias', 'Dashboard\MediasController@getMedias' );
Route::delete( 'medias/{id}', 'Dashboard\MediasController@deleteMedia' );
Route::put( 'medias/{id}', 'Dashboard\MediasController@updateMedia' );
Route::post( 'medias/bulk-delete/', 'Dashboard\MediasController@bulkDeleteMedias' );
Route::post( 'medias', 'Dashboard\MediasController@uploadMedias' );