<?php

namespace Tests\Feature;

use App\Models\Register;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteRegisterTest extends TestCase
{
    public $data;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateRegister()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.registers', [
                'name'                  =>  __( 'Register' ),
                'general'               =>  [
                    'status'            =>  Register::STATUS_CLOSED
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        global $argv;

        $argv       =   json_decode( $response->getContent(), true );
    }

    public function testDeleteRegister()
    {
        global $argv;

        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
        ->json( 'DELETE', 'api/nexopos/v4/crud/ns.registers/' . $argv[ 'entry' ][ 'id' ], [
            'name'                  =>  __( 'Register' ),
            'general'               =>  [
                'status'            =>  Register::STATUS_CLOSED
            ]
        ]);        

        $response->assertJson([
            'status'    =>  'success'
        ]);
    }
}
