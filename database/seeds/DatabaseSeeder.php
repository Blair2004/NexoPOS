<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        DB::table( 'nexopos_units' )->truncate();
        DB::table( 'nexopos_units_groups' )->truncate();
        DB::table( 'nexopos_taxes' )->truncate();
        DB::table( 'nexopos_taxes_groups' )->truncate();
        DB::table( 'nexopos_products' )->truncate();
        DB::table( 'nexopos_products_categories' )->truncate();
        DB::table( 'nexopos_products_galleries' )->truncate();
        
        $this->call( RewardSystemSeeder::class );
        $this->call( CustomerGroupSeeder::class );
        $this->call( UnitGroupSeeder::class );
        $this->call( TaxSeeder::class );
        $this->call( ProductsSeeder::class );
    }
}
