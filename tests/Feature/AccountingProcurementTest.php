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
    use WithAuthentication, WithAccountingTest, WithProcurementTest, WithProviderTest;

    public function testCreateProcurement()
    {
        $this->attemptAuthenticate();
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