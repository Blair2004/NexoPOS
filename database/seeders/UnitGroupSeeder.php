<?php

namespace Database\Seeders;

use App\Models\UnitGroup;
use Illuminate\Database\Seeder;

class UnitGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        return UnitGroup::factory()
            ->count( 5 )
            ->hasUnits( 8 )
            ->create();
    }
}
