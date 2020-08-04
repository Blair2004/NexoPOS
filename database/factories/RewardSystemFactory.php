<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\RewardSystem;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define( RewardSystem::class, function (Faker $faker) {
    return [
        'name'      =>      $faker->company,
        'target'    =>      $faker->numberBetween(500,10000),
        'author'    =>      $faker->randomElement( User::get()->map( fn( $user ) => $user->id ) )
    ];
});
