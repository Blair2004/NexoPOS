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

        for ( $i = 0; $i < 5; $i++ ) {
            $this->attemptCreateCustomerWithInitialTransactions();
        }
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

    public function testAttemptSearchCustomer()
    {
        $this->attemptAuthenticate();
        $this->attemptSearchCustomers();
    }
}
