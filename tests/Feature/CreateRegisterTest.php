<?php

namespace Tests\Feature;

use App\Models\Register;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateRegisterTest extends TestCase
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

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.registers', [
                'name'                  =>  __( 'Cash Register' ),
                'general'               =>  [
                    'status'            =>  Register::STATUS_CLOSED
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);
    }
}
