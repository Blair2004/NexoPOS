<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCustomerTest;

class CustomerRouteTest extends TestCase
{
    use WithAuthentication, WithCustomerTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCustomerRoutes()
    {
        $this->attemptAuthenticate();
        $this->attemptGetCustomerHistory();
        $this->attemptGetCustomerHistory();
        $this->attemptGetCustomerOrders();
        $this->attemptGetCustomerReward();
        $this->attemptGetOrdersAddresses();
        $this->attemptGetCustomerGroup();
    }
}
