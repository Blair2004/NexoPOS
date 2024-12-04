<?php

namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Classes\Hook;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition()
    {
        return Hook::filter( 'ns-unit-factory', [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'base_unit' => $this->faker->randomElement( [ 0, 1 ] ),
            'value' => $this->faker->numberBetween( 5, 20 ),
            'author' => $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
        ] );
    }
}
