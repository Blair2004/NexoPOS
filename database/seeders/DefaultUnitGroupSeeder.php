<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Unit;
use App\Models\UnitGroup;
use Illuminate\Database\Seeder;

class DefaultUnitGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $unitGroup = UnitGroup::create( [
            'name' => __( 'Countable' ),
            'author' => Role::namespace( 'admin' )->users->first()->id,
        ] );

        $piece = Unit::create( [
            'name' => __( 'Piece' ),
            'value' => 1,
            'identifier' => 'piece',
            'base_unit' => true,
            'group_id' => $unitGroup->id,
            'author' => Role::namespace( 'admin' )->users->first()->id,
        ] );
    }
}
