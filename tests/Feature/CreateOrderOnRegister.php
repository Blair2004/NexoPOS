<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateOrderOnRegister extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_order_on_register()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateOrderOnRegister();
    }

    public function test_update_order_on_register()
    {
        $this->attemptAuthenticate();
        $this->attemptUpdateOrderOnRegister();
    }
}
