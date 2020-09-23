<?php
namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\Tax;
use App\Models\User;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    protected $model    =   Tax::class;

    public function definition()
    {
        return [
            'name'          =>  $this->faker->name,
            'description'   =>  $this->faker->sentence,
            'rate'          =>  $this->faker->numberBetween( 1, 20 ),
            'author'        =>  $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
        ];
    }
}
