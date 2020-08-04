<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\RewardSystemRule;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(App\Models\RewardSystemRule::class, function (Faker $faker) {
    return [
        'from'          =>  $faker->numberBetween(5,10),
        'to'            =>  $faker->numberBetween(40,80),
        'reward'        =>  $faker->numberBetween(100, 200 ),
        'author'        =>  $faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
    ];
});
