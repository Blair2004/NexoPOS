<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        return Customer::factory()
            ->count(10)
            ->afterCreating( function( $model ) {
                $user   =   User::find( $model->id );
                $user->assignRole( Role::STORECUSTOMER );
            })
            ->hasShipping(1)
            ->hasBilling(1)
            ->create();
    }
}
