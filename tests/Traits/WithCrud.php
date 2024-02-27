<?php

namespace Tests\Traits;

use Illuminate\Testing\TestResponse;

trait WithCrud
{
    public function submitRequest( $namespace, $data = [], $method = 'POST' ): TestResponse
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( $method, 'api/crud/' . $namespace, $data );

        return $response;
    }
}
