<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DefaultProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        return Provider::create([
            'name'      =>  __( 'Default Provider' ),
            'author'    =>  Role::namespace( 'admin' )->users->first()->id
        ]);
    }
}
