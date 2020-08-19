<?php

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
        factory( App\Models\TaxGroup::class, 5 )->create()->each( function( $taxGroup ) {
            $taxGroup->taxes()->saveMany(
                factory( App\Models\Tax::class, 5 )->make()
            );
        });
    }
}
