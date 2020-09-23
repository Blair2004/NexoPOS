<?php
namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\RewardSystemRule;
use App\Models\User;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardSystemRuleFactory extends Factory
{
    protected $model    =   RewardSystemRule::class;

    public function definition()
    {
        return [
            'from'          =>  $this->faker->numberBetween(5,10),
            'to'            =>  $this->faker->numberBetween(40,80),
            'reward'        =>  $this->faker->numberBetween(100, 200 ),
            'author'        =>  $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
        ];
    }
}
