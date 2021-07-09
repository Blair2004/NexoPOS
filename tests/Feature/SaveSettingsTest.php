<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Role;
use App\Models\Option;
use Laravel\Sanctum\Sanctum;


class SaveSettingsTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testSaveSettings()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $response = $this
            ->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', '/api/nexopos/v4/settings/ns.pos', [
                'printing' =>  [
                    'ns_pos_printing_gateway'   =>  'default'
                ]
            ]);

        $this->assertTrue( ns()->option->get( 'ns_pos_printing_gateway' ) === 'default' );

        return $response->assertJsonPath( 'status', 'success' );
    }
}
