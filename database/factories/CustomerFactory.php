<?php

namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition()
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'username'  =>  $this->faker->userName(),
            'password'  =>  $this->faker->password(),
            'email' => $this->faker->email(),
            'gender' => $this->faker->randomElement([ 'male', 'female', '' ]),
            'phone' => $this->faker->phoneNumber(),
            'pobox' => $this->faker->postcode(),
            'author' => $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
            'group_id' => $this->faker->randomElement( CustomerGroup::get()->map( fn( $group ) => $group->id ) ),
        ];
    }
}
