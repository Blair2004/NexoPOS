<?php
namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAccountingTest;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCategoryTest;
use Tests\Traits\WithCustomerTest;
use Tests\Traits\WithProductTest;
use Tests\Traits\WithProviderTest;
use Tests\Traits\WithTaxTest;
use Tests\Traits\WithUnitTest;

class AccountingPreTest extends TestCase
{
    use WithAuthentication, WithUnitTest, WithCategoryTest, WithProductTest, WithTaxTest, WithAccountingTest, WithProviderTest, WithCustomerTest;

    public function testCreateAccounts()
    {
        $this->attemptAuthenticate();
        $this->createDefaultAccounts();
    }

    public function testCreateCustomers()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomerGroup();
        $this->attemptCreateCustomer();
    }

    public function testCreateTaxes()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateTaxGroup();
        $this->attemptCreateTax();
    }

    public function testCreateUnits()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateUnitGroup();
        $this->attemptCreateUnit();
    }

    public function testCreateCategory()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCategory();
    }

    public function testCreateProduct()
    {
        $this->attemptAuthenticate();
        $this->attemptSetProduct();
    }

    public function testCreateProviders()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProvider();
    }
}