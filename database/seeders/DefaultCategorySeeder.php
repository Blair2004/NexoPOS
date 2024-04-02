<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        return ProductCategory::create( [
            'name' => __( 'Default Category' ),
            'author' => Role::namespace( 'admin' )->users->first()->id,
        ] );
    }
}
