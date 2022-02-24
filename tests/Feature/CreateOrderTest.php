<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateOrderTest extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder( $callback = null )
    {
        if ( $this->defaultProcessing ) {
            $this->attemptAuthenticate();
    
            return $this->attemptPostOrder( $callback );
        } else {
            $this->assertTrue( true ); // because we haven't performed any test.
        }
    }
}
