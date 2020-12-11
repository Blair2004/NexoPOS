<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UnitGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateUnitTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $group          =   UnitGroup::get()->shuffle()->first();

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.units', [
                'name'          =>  __( 'Piece' ),
                'general'       =>  [
                    'base_unit'     =>  true,
                    'value'         =>  1,
                    'group_id'      =>  $group->id
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.units', [
                'name'          =>  __( 'Dozen' ),
                'general'       =>  [
                    'base_unit'     =>  false,
                    'value'         =>  12,
                    'group_id'      =>  $group->id
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.units', [
                'name'          =>  __( 'Thirty' ),
                'general'       =>  [
                    'base_unit'     =>  false,
                    'value'         =>  30,
                    'group_id'      =>  $group->id
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);
    }
}
