<?php
namespace Database\Seeders;

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
        return CustomerGroup::factory()
            ->count(10)
            ->hasCustomers(3)
            ->create();
    }
}
