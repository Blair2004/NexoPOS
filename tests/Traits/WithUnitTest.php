<?php

namespace Tests\Traits;

use App\Models\UnitGroup;

trait WithUnitTest
{
    protected function attemptCreateUnitGroup()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.units-groups', [
                'name' => __( 'Liquids' ),
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $response->assertStatus( 200 );
    }

    protected function attemptCreateUnit()
    {
        $group = UnitGroup::get()->shuffle()->first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.units', [
                'name' => __( 'Piece' ),
                'general' => [
                    'base_unit' => true,
                    'value' => 1,
                    'identifier' => 'piece',
                    'group_id' => $group->id,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.units', [
                'name' => __( 'Dozen' ),
                'general' => [
                    'base_unit' => false,
                    'value' => 12,
                    'identifier' => 'dozen',
                    'group_id' => $group->id,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.units', [
                'name' => __( 'Thirty' ),
                'general' => [
                    'base_unit' => false,
                    'value' => 30,
                    'identifier' => 'thirty',
                    'group_id' => $group->id,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );
    }
}
