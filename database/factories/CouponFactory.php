<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => __( 'Sample Coupon' ),
            'type' => 'percentage_discount',
            'code' => 'CP-' . ( $this->faker->randomDigit ) . ( $this->faker->randomDigit ) . ( $this->faker->randomDigit ) . ( $this->faker->randomDigit ) . ( $this->faker->randomDigit ),
            'author' => $this->faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
            'discount_value' => $this->faker->randomElement( [ 10, 15, 20, 25 ] ),
            'limit_usage' => $this->faker->randomElement( [ 1, 5, 10 ] ),
        ];
    }
}
