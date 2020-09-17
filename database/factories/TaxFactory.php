<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\Tax;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define( Tax::class, function (Faker $faker) {
    return [
        'name'  =>  $faker->name,
        'description'   =>  $faker->sentence,
        'rate'          =>  $faker->numberBetween( 1, 20 ),
        'author'        =>  $faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
    ];
});
