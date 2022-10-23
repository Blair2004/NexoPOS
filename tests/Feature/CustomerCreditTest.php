<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCustomerTest;

class CustomerCreditTest extends TestCase
{
    use WithAuthentication, WithCustomerTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAddCredit()
    {
        $this->attemptAuthenticate();
        $this->attemptCreditCustomerAccount();
    }

    public function testRemoveCredit()
    {
        $this->attemptAuthenticate();
        $this->attemptRemoveCreditCustomerAccount();
    }
}
