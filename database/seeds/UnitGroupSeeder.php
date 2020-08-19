<?php

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
        factory( App\Models\UnitGroup::class, 5 )->create()->each( function( $taxGroup ) {
            $taxGroup->units()->saveMany(
                factory( App\Models\Unit::class, 8 )->make()
            );
        });
    }
}
