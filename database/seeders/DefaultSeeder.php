<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call( DefaultCategorySeeder::class );
        $this->call( DefaultUnitGroupSeeder::class );
        $this->call( DefaultProviderSeeder::class );
        $this->call( CustomerGroupSeeder::class );
    }
}
