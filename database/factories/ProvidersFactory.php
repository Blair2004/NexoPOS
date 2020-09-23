<?php
namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\Provider;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProvidersFactory extends Factory
{
    protected $model    =   Provider::class;

    public function definition()
    {
        return [
            //
        ];
    }
}
