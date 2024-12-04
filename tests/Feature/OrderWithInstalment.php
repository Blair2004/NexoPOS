<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class OrderWithInstalment extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_order_with_instalment()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateOrderWithInstalment();
    }
}
