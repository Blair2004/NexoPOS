<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FirstDemoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {       
        $this->call( RewardSystemSeeder::class );
        $this->call( CustomerGroupSeeder::class );
        $this->call( ExpensesSeeder::class );
        $this->call( FirstExampleProviderSeeder::class );
    }
}
