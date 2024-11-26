<?php

namespace Database\Factories;

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

        return [
            'quantity' => $this->faker->numberBetween( 50, 400 ),
            'sale_price' => $sale_price,
            'sale_price_edit' => $sale_price,
            'excl_tax_sale_price' => $sale_price,
            'incl_tax_sale_price' => $sale_price,
            'sale_price_tax' => 0,
            'wholesale_price' => $wholesale_price,
            'wholesale_price_edit' => $wholesale_price,
            'incl_tax_wholesale_price' => $wholesale_price,
            'excl_tax_wholesale_price' => $wholesale_price,
            'wholesale_price_tax' => 0,
        ];
    }
}
