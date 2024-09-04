<?php
namespace Tests\Feature;

use Modules\NsGastro\Tests\TestCase;
use Tests\Traits\WithAccountingTest;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCategoryTest;
use Tests\Traits\WithProcurementTest;
use Tests\Traits\WithProductTest;
use Tests\Traits\WithProviderTest;
use Tests\Traits\WithTaxTest;
use Tests\Traits\WithUnitTest;

class AccountingProcurementTest extends TestCase
{
    use WithAuthentication, WithAccountingTest, WithProcurementTest, WithProviderTest, WithProductTest, WithCategoryTest, WithUnitTest, WithTaxTest;

    public function testCreateAccounts()
    {
        $this->attemptAuthenticate();
        $this->createDefaultAccounts();
        $this->createProcurementsAccounts();
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

    public function testCreateProcurement()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProvider();
        $this->attemptCreateAnUnpaidProcurement();
    }

    public function testCreateProcurementAndPay()
    {
        // $this->attemptAuthenticate();
        // $this->attemptUnpaidProcurement();
    }
}