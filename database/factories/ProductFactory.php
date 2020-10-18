<?php
namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\Product;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Models\User;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model    =   Product::class;

    public function definition()
    {
        $unitGroup                  =   $this->faker->randomElement( UnitGroup::get() );

        return [
            'name'                  =>  $this->faker->word,
            'product_type'          =>  'product',    
            'barcode'               =>  $this->faker->word,
            'stock_management'      =>  $this->faker->randomElement([ 'enabled', 'disabled' ]),
            'barcode_type'          =>  $this->faker->randomElement([ 'ean8', 'ean13' ]),
            'sku'                   =>  $this->faker->word . date( 's' ),
            'product_type'          =>  $this->faker->randomElement([ 'materialized', 'dematerialized']),
            'unit_group'            =>  $unitGroup->id,
            'author'                =>  $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
        ];
    }
}