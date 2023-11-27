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
    public function testCreateProcurement()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProcurement();
    }

    public function testCreateUnpaidProcurement()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateAnUnpaidProcurement();
    }

    public function testCreateProcurementWithConversion()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProcurementWithConversion();
    }

    public function testDeleteProcurement()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteProcurement();
    }

    public function testDeleteProcurementWithConvertedProducts()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteProcurementWithConvertedProducts();
    }
}
