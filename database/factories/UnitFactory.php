<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\Unit;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define( Unit::class, function (Faker $faker) {
    return [
        'name'  =>  $faker->name,
        'description'   =>  $faker->sentence,
        'base_unit'     =>  $faker->randomElement([ 0, 1 ]),
        'value'         =>  $faker->numberBetween( 5, 20 ),
        'author'        =>  $faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
    ];
});
