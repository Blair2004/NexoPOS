<?php
namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\TaxGroup;
use App\Models\User;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxGroupFactory extends Factory
{
    protected $model    =   TaxGroup::class;

    public function definition()
    {
        return [
            'name'          =>  $this->faker->word,
            'description'   =>  $this->faker->sentence,
            'author'        =>  $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),    
        ];
    }
}
