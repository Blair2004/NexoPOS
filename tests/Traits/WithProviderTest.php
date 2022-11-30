<?php

namespace Tests\Traits;

trait WithProviderTest
{
    protected function attemptCreateProvider()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.providers', [
                'name' => __( 'Computers' ),
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);
    }
}
