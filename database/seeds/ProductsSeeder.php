<?php

use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory( App\Models\ProductCategory::class, 10 )->create()->each( function( $category ) {
            $category->products()->saveMany(
                factory( App\Models\Product::class, 20 )->make()
            );
        });
    }
}
