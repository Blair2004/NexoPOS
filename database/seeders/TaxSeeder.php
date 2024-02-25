<?php

namespace Database\Seeders;

use App\Models\TaxGroup;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        return TaxGroup::factory()
            ->count( 5 )
            ->hasTaxes( 2 )
            ->create();
    }
}
