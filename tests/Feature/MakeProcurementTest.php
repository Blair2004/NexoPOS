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
}
