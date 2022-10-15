<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerBillingAddress;
use App\Models\CustomerGroup;
use App\Models\CustomerShippingAddress;
use Illuminate\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        return CustomerGroup::factory()
            ->count(10)
            ->has(
                Customer::factory()
                    ->count(3)
                    ->has(
                        CustomerShippingAddress::factory()->count(1), 'shipping'
                    )
                    ->has(
                        CustomerBillingAddress::factory()->count(1), 'billing'
                    ),
                'customers'
            )
            ->create();
    }
}
