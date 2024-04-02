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
    private function testCreateProcurement()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProcurement();
    }

    private function testCreateUnpaidProcurement()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateAnUnpaidProcurement();
    }

    private function testCreateProcurementWithConversion()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProcurementWithConversion();
    }

    public function testDeleteProcurement()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteProcurement();
    }

    private function testDeleteProcurementWithConvertedProducts()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteProcurementWithConvertedProducts();
    }
}
