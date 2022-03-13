<?php
namespace Tests\Traits;

use Illuminate\Testing\TestResponse;

trait WithCrud
{
    public function submitRequest( $namespace, $data = [], $method = 'POST' ): TestResponse
    {
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( $method, 'api/nexopos/v4/crud/' . $namespace, $data );

        return $response;
    }
}