<?php

namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Classes\Hook;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition()
    {
        return Hook::filter( 'ns-customer-factory', [
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'username' => $this->faker->userName(),
            'password' => Hash::make( $this->faker->password() ),
            'email' => $this->faker->email(),
            'active' => true,
            'gender' => $this->faker->randomElement( [ 'male', 'female', '' ] ),
            'phone' => $this->faker->phoneNumber(),
            'pobox' => $this->faker->postcode(),
            'author' => $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
            'group_id' => $this->faker->randomElement( CustomerGroup::get()->map( fn( $group ) => $group->id ) ),
        ] );
    }

    public function configure(): static
    {
        return $this->afterCreating( function ( $model ) {
            $user = User::find( $model->id );
            $user->assignRole( Role::STORECUSTOMER );
        } );
    }
}
