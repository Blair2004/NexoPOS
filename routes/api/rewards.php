<?php
Route::get( 'reward-system', 'RewardSystemController@list' );
Route::get( 'reward-system/{id}/rules', 'RewardSystemController@getRules' );
Route::get( 'reward-system/{id}/coupons', 'RewardSystemController@getRegisterOrders' );
Route::post( 'reward-system', 'RewardSystemController@create' );
Route::delete( 'reward-system/{id}', 'RewardSystemController@deleteRewardSystem' );
Route::put( 'reward-system/{id}', 'RewardSystemController@editRewardSystem' );