<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Services\Helper;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResetTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        if ( Helper::installed() ) {
            Sanctum::actingAs(
                Role::namespace( 'admin' )->users->first(),
                ['*']
            );

            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/reset', [
                    'mode' => 'wipe_plus_grocery',
                    'create_sales' => true,
                    'create_procurements' => true,
                ] );

            $response->assertJson( [
                'status' => 'success',
            ] );

            $response->assertStatus( 200 );
        } else {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/hard-reset', [
                    'authorization' => env( 'NS_AUTHORIZATION' ),
                ] );

            $response->assertJson( [
                'status' => 'success',
            ] );
        }
    }
}
