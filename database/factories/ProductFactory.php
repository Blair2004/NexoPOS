<?php

namespace Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Classes\Hook;
use App\Models\Product;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Models\User;
use App\Services\TaxService;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        $unitGroup = $this->faker->randomElement( UnitGroup::get() );

        /**
         * @var TaxService
         */
        $taxType = $this->faker->randomElement( [ 'inclusive', 'exclusive' ] );
        $taxGroup = TaxGroup::get()->first();

        return Hook::filter( 'ns-product', [
            'name' => $this->faker->word,
            'product_type' => 'product',
            'barcode' => $this->faker->word,
            'tax_type' => $taxType,
            'tax_group_id' => $taxGroup->id, // assuming there is only one group
            'stock_management' => $this->faker->randomElement( [ 'enabled', 'disabled' ] ),
            'barcode_type' => $this->faker->randomElement( [ 'ean13' ] ),
            'sku' => $this->faker->word . date( 's' ),
            'type' => $this->faker->randomElement( [ 'materialized', 'dematerialized'] ),
            'unit_group' => $unitGroup->id,
            'author' => $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
        ] );
    }
}
