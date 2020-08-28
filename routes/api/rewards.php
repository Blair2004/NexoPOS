<?php
Route::get( 'reward-system', 'Dashboard\RewardSystemController@list' );
Route::get( 'reward-system/{id}/rules', 'Dashboard\RewardSystemController@getRules' );
Route::get( 'reward-system/{id}/coupons', 'Dashboard\RewardSystemController@getRegisterOrders' );
Route::post( 'reward-system', 'Dashboard\RewardSystemController@create' );
Route::delete( 'reward-system/{id}', 'Dashboard\RewardSystemController@deleteRewardSystem' );
Route::put( 'reward-system/{id}', 'Dashboard\RewardSystemController@editRewardSystem' );