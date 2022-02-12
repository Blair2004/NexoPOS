<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResetTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        if ( ns()->installed() ) {
            Sanctum::actingAs(
                Role::namespace( 'admin' )->users->first(),
                ['*']
            );

            $response   =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/reset', [
                    'mode'                  =>  'wipe_plus_grocery',
                    'create_sales'          =>  true,
                    'create_procurements'   =>  true
                ]);
            
            $response->assertJson([
                'status'    =>  'success',
            ]);
    
            $response->assertStatus(200);
        } else {
            $response   =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/hard-reset', [
                    'authorization' =>  env( 'NS_AUTHORIZATION' )
                ]);
        
            $response->assertJson([
                'status'    =>  'success'
            ]);
        }
    }
}
