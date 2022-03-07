<?php
namespace Tests\Traits;

trait WithProviderTest
{
    protected function attemptCreateProvider()
    {
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.providers', [
                'name'                  =>  __( 'Computers' ),
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);
    }
}