<?php
namespace Database\Seeders;

use App\Models\ProductCategory;
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
        return ProductCategory::factory()
            ->count(10)
            ->hasProducts(20)
            ->create();
    }
}
