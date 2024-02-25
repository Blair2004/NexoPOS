<?php

namespace Database\Seeders;

use App\Models\CustomerGroup;
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
            ->count( 10 )
            ->hasCustomers( 10 )
            ->create();
    }
}
