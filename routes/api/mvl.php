<?php

use App\Http\Controllers\MVLApiController;
use Illuminate\Support\Facades\Route;

Route::get( 'mvl/customers', [ MVLApiController::class, 'getCustomers' ]);
Route::post('mvl/customer/{id}/pin', [ MVLApiController::class, 'verifyCustomerPin' ])->where('id', '[0-9]+');
Route::get('mvl/products', [ MVLApiController::class, 'getProducts' ]);
Route::post('mvl/customer/{id}/order', [ MVLApiController::class, 'order' ])->where('id', '[0-9]+');
