<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateCustomPaymentTypeAndOrder extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_payment_type_and_order()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomPaymentType();
        $this->attemptCreateCustomerOrder();
    }
}
