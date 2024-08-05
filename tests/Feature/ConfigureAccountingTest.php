<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAccountingTest;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCategoryTest;
use Tests\Traits\WithProductTest;
use Tests\Traits\WithProviderTest;
use Tests\Traits\WithTaxTest;
use Tests\Traits\WithUnitTest;

class ConfigureAccountingTest extends TestCase
{
    use WithAccountingTest, WithAuthentication, WithProviderTest, WithProductTest, WithCategoryTest, WithUnitTest, WithTaxTest, WithProviderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateBankingAccounts()
    {
        $this->attemptAuthenticate();
        $this->createDefaultAccounts();
    }

    public function createDefaultProviders()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProvider();
    }

    public function testCreateTax()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateTaxGroup();
        $this->attemptCreateTax();
    }

    public function testCreateUnitGroupAndUnit()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateUnitGroup();
        $this->attemptCreateUnit();
    }

    public function testCreateProvider()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProvider();
    }

    public function testCreateProducts()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCategory();
        $this->attemptSetProduct();
    }

    public function testProcurementAccountingsTests()
    {
        $this->attemptAuthenticate();
        $this->attemptPaidProcurement();
        $this->attemptUnpaidProcurement();
        $this->attemptDeleteProcurement();
    }
}
