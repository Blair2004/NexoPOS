<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\ProductCategory;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define( ProductCategory::class, function (Faker $faker) {
    return [
        'name'              =>  $faker->name,
        'description'       =>  $faker->sentence,
        'displays_on_pos'   =>  $faker->randomElement([ true, false ]),
        'author'            =>  $faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
    ];
});
