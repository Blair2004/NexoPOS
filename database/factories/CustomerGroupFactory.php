<?php

namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Classes\Hook;
use App\Models\CustomerGroup;
use App\Models\RewardSystem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerGroupFactory extends Factory
{
    protected $model = CustomerGroup::class;

    public function definition()
    {
        return Hook::filter( 'ns-customer-group-factory', [
            'name' => $this->faker->catchPhrase(),
            'minimal_credit_payment' => $this->faker->numberBetween( 0, 50 ),
            'author' => $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
            'reward_system_id' => $this->faker->randomElement( RewardSystem::get()->map( fn( $reward ) => $reward->id ) ),
        ] );
    }
}
