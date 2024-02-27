<?php

namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\RewardSystemRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardSystemRuleFactory extends Factory
{
    protected $model = RewardSystemRule::class;

    public function definition()
    {
        return [
            'from' => 0,
            'to' => $this->faker->numberBetween( 100, 500 ),
            'reward' => $this->faker->numberBetween( 100, 200 ),
            'author' => $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
        ];
    }
}
