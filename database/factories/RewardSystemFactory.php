<?php

namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Coupon;
use App\Models\RewardSystem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardSystemFactory extends Factory
{
    protected $model = RewardSystem::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'target' => $this->faker->numberBetween( 500, 10000 ),
            'coupon_id' => $this->faker->randomElement( Coupon::get()->map( fn( $user ) => $user->id ) ),
            'author' => $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
        ];
    }
}
