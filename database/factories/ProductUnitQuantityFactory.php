<?php

namespace Database\Factories;

use App\Classes\Hook;
use App\Models\ProductUnitQuantity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductUnitQuantityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductUnitQuantity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $sale_price = $this->faker->numberBetween( 20, 30 );
        $wholesale_price = $this->faker->numberBetween( 10, 20 );

        return Hook::filter( 'ns-product-unit-quantity-factory', [
            'quantity' => $this->faker->numberBetween( 50, 400 ),
            'sale_price' => $sale_price,
            'sale_price_edit' => $sale_price,
            'sale_price_net' => $sale_price,
            'sale_price_gross' => $sale_price,
            'sale_price_tax' => 0,
            'wholesale_price' => $wholesale_price,
            'wholesale_price_edit' => $wholesale_price,
            'wholesale_price_gross' => $wholesale_price,
            'wholesale_price_net' => $wholesale_price,
            'wholesale_price_tax' => 0,
        ] );
    }
}
