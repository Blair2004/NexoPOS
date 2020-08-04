<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\User;
use App\Models\RewardSystem;
use App\Models\CustomerGroup;
use Faker\Generator as Faker;

$factory->define(CustomerGroup::class, function (Faker $faker) {
    return [
        'name'              =>  $faker->catchPhrase,
        'author'            =>  $faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
        'reward_system_id'  =>  $faker->randomElement( RewardSystem::get()->map( fn( $reward ) => $reward->id ) ),
    ];
});
