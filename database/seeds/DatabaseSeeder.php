<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table( 'nexopos_rewards_system' )->truncate();
        DB::table( 'nexopos_rewards_system_rules' )->truncate();
        DB::table( 'nexopos_customers' )->truncate();
        DB::table( 'nexopos_customers_groups' )->truncate();
        
        $this->call( RewardSystemSeeder::class );
        $this->call( CustomerGroupSeeder::class );
    }
}
