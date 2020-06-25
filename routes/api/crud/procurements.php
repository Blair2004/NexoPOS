<?php
Route::get( '/crud/{namespace}', 'CrudController@list' );
Route::post( '/crud/{namespace}', 'CrudController@post' );