<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProcurementTest;

class MakeProcurementTest extends TestCase
{
    use WithAuthentication, WithProcurementTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    private function test_create_procurement()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProcurement();
    }

    private function test_create_unpaid_procurement()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateAnUnpaidProcurement();
    }

    private function test_create_procurement_with_conversion()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProcurementWithConversion();
    }

    public function test_delete_procurement()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteProcurement();
    }

    private function test_delete_procurement_with_converted_products()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteProcurementWithConvertedProducts();
    }
}
