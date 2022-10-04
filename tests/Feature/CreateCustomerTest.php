<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCustomerTest;

class CreateCustomerTest extends TestCase
{
    use WithAuthentication, WithCustomerTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateCustomers()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomer();
    }

    public function testCreateCustomerWithNoEmail()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomerWithNoEmail();
    }

    public function testCreateCustomersWithSimilarEmail()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomersWithSimilarEmail();
    }
}
