<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCashRegisterTest;

class CreateRegisterTest extends TestCase
{
    use WithAuthentication, WithCashRegisterTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateRegister()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateRegisterTransactions();
    }
}
