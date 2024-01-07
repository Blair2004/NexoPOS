<?php

namespace Database\Factories;

use App\Models\CustomerShippingAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class CustomerShippingAddressFactory extends Factory
{
    protected $model = CustomerShippingAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $email = $this->faker->email();

        return [
            'type' => 'shipping',
            'email' => $email,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
            'address_1' => $this->faker->streetAddress(),
            'address_2' => $this->faker->streetAddress(),
            'country' => $this->faker->country(),
            'city' => $this->faker->city(),
            'pobox' => $this->faker->postcode(),
            'author' => User::get()->random()->id,
        ];
    }
}
