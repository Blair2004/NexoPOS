<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAccountingTest;
use Tests\Traits\WithAuthentication;

class ConfigureAccoutingTest extends TestCase
{
    use WithAccountingTest, WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateBankingAccounts()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateBankingAccounts();
    }

    public function testCheckSalesTaxes()
    {
        $this->attemptAuthenticate();
        $this->attemptCheckSalesTaxes();
    }
}
