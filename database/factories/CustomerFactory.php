<?php
namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\User;
use App\Models\CustomerGroup;
use App\Models\Customer;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model    =   Customer::class;

    public function definition()
    {
        return [
            'name'      =>  $this->faker->name(),
            'surname'   =>  $this->faker->name(),
            'email'     =>  $this->faker->email(),
            'gender'    =>  $this->faker->randomElement([ 'male', 'female', '' ]),
            'phone'     =>  $this->faker->phoneNumber(),
            'pobox'     =>  $this->faker->postcode(),
            'author'    =>  $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
            'group_id'  =>  $this->faker->randomElement( CustomerGroup::get()->map( fn( $group ) => $group->id ) ),
        ];
    }
}
