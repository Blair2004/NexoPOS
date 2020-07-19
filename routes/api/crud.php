<?php

use Illuminate\Support\Facades\Route;

Route::get( 'crud/{namespace}', 'Dashboard\CrudController@crudList' );
Route::get( 'crud/{namespace}/columns', 'Dashboard\CrudController@getColumns' );
Route::get( 'crud/{namespace}/fields', 'Dashboard\CrudController@fields' );
Route::get( 'crud/{namespace}/config', 'Dashboard\CrudController@getConfig' );
Route::get( 'crud/{namespace}/form-config/{id?}', 'Dashboard\CrudController@getFormConfig' );
Route::put( 'crud/{namespace}/{id}', 'Dashboard\CrudController@crudPut' )->where(['id' => '[0-9]+']);
Route::post( 'crud/{namespace}', 'Dashboard\CrudController@crudPost' );
Route::post( 'crud/{namespace}/bulk-actions', 'Dashboard\CrudController@crudBulkActions' );
Route::post( 'crud/{namespace}/can-access', 'Dashboard\CrudController@canAccess' );
Route::delete( 'crud/{namespace}/{id}', 'Dashboard\CrudController@crudDelete' );