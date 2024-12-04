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
    use WithAccountingTest, WithAuthentication, WithCategoryTest, WithCustomerTest, WithProductTest, WithProviderTest, WithTaxTest, WithUnitTest;

    public function test_create_accounts()
    {
        $this->attemptAuthenticate();
        $this->createDefaultAccounts();
    }

    public function test_create_customers()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomerGroup();
        $this->attemptCreateCustomer();
    }

    public function test_create_taxes()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateTaxGroup();
        $this->attemptCreateTax();
    }

    public function test_create_units()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateUnitGroup();
        $this->attemptCreateUnit();
    }

    public function test_create_category()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCategory();
    }

    public function test_create_product()
    {
        $this->attemptAuthenticate();
        $this->attemptSetProduct();
    }

    public function test_create_providers()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProvider();
    }
}
