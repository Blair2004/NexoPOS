<?php
namespace Tests\Feature;

use App\Models\Procurement;
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
        $response = $this->attemptCreateAnUnpaidProcurement();
        $procurement    =   Procurement::findOrFail( $response[ 'data' ][ 'procurement' ][ 'id' ] );
        $this->attemptTestAccountingForProcurement( $procurement );

        return $procurement;
    }

    public function testCreatePaidProcurement()
    {
        $this->attemptAuthenticate();
        $response = $this->attemptPaidProcurement();

        $this->attemptTestAccountingForProcurement( Procurement::findOrFail( $response[ 'data' ][ 'procurement' ][ 'id' ] ) );
    }

    /**
     * @depends testCreateProcurement
     */
    public function testCreateProcurementAndPay( $procurement )
    {
        $this->attemptAuthenticate();
        $this->attemptPayUnpaidProcurement( $procurement->id );
        $this->attemptTestAccountingForPreviouslyUnpaidProcurement( $procurement );
    }
}