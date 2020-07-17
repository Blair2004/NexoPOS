<?php

use Illuminate\Support\Facades\Route;

Route::get( 'crud/{namespace}', 'CrudController@crudList' );
Route::get( 'crud/{namespace}/columns', 'CrudController@getColumns' );
Route::get( 'crud/{namespace}/fields', 'CrudController@fields' );
Route::get( 'crud/{namespace}/config', 'CrudController@getConfig' );
Route::get( 'crud/{namespace}/form-config/{id?}', 'CrudController@getFormConfig' );
Route::put( 'crud/{namespace}/{id}', 'CrudController@crudPut' )->where(['id' => '[0-9]+']);
Route::post( 'crud/{namespace}', 'CrudController@crudPost' );
Route::post( 'crud/{namespace}/bulk-actions', 'CrudController@crudBulkActions' );
Route::post( 'crud/{namespace}/can-access', 'CrudController@canAccess' );
Route::delete( 'crud/{namespace}/{id}', 'CrudController@crudDelete' );