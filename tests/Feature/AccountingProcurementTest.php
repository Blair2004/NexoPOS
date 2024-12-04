<?php

namespace Tests\Feature;

use App\Models\Procurement;
use Modules\NsGastro\Tests\TestCase;
use Tests\Traits\WithAccountingTest;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProcurementTest;
use Tests\Traits\WithProviderTest;

class AccountingProcurementTest extends TestCase
{
    use WithAccountingTest, WithAuthentication, WithProcurementTest, WithProviderTest;

    public function test_create_procurement()
    {
        $this->attemptAuthenticate();
        $response = $this->attemptCreateAnUnpaidProcurement();
        $procurement = Procurement::findOrFail( $response[ 'data' ][ 'procurement' ][ 'id' ] );
        $this->attemptTestAccountingForProcurement( $procurement );

        return $procurement;
    }

    public function test_create_paid_procurement()
    {
        $this->attemptAuthenticate();
        $response = $this->attemptPaidProcurement();

        $this->attemptTestAccountingForProcurement( Procurement::findOrFail( $response[ 'data' ][ 'procurement' ][ 'id' ] ) );
    }

    /**
     * @depends test_create_procurement
     */
    public function test_create_procurement_and_pay( $procurement )
    {
        $this->attemptAuthenticate();
        $this->attemptPayUnpaidProcurement( $procurement->id );
        $this->attemptTestAccountingForPreviouslyUnpaidProcurement( $procurement );
    }
}
