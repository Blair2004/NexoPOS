<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\CustomerGroup;
use App\Models\Customer;
use App\Models\RewardSystem;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory( CustomerGroup::class, 10 )->create()->each( function( $group ) {
            $group->customers()->saveMany( factory( Customer::class, 10 )->make() );
        });
    }
}
