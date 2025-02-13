<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCustomerTest;

class CreateCustomerGroupTest extends TestCase
{
    use WithAuthentication, WithCustomerTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_customer_group()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomerGroup();
    }
}
