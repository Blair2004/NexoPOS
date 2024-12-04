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
    public function test_create_customers()
    {
        $this->attemptAuthenticate();

        for ( $i = 0; $i < 5; $i++ ) {
            $this->attemptCreateCustomerWithInitialTransactions();
        }
    }

    public function test_create_customer_with_no_email()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomerWithNoEmail();
    }

    public function test_create_customers_with_similar_email()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomersWithSimilarEmail();
    }

    public function test_attempt_search_customer()
    {
        $this->attemptAuthenticate();
        $this->attemptSearchCustomers();
    }
}
