<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\User;
use App\Models\CustomerGroup;
use App\Models\Customer;
use Faker\Generator as Faker;

$factory->define(Customer::class, function (Faker $faker) {
    return [
        'name'      =>  $faker->name,
        'surname'   =>  $faker->name,
        'email'     =>  $faker->email,
        'gender'    =>  $faker->randomElement([ 'male', 'female', '' ]),
        'phone'     =>  $faker->phoneNumber,
        'pobox'     =>  $faker->postcode,
        'author'    =>  $faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
        'group_id'  =>  $faker->randomElement( CustomerGroup::get()->map( fn( $group ) => $group->id ) ),
    ];
});
