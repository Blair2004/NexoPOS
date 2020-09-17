<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\TaxGroup;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define( TaxGroup::class, function (Faker $faker) {
    return [
        'name'          =>  $faker->word,
        'description'   =>  $faker->sentence,
        'author'        =>  $faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),    
    ];
});
