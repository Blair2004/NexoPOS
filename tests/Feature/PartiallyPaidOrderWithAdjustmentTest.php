<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class PartiallyPaidOrderWithAdjustmentTest extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateOrderWithPartialPayment()
    {
        $this->attemptAuthenticate();
        $this->attemptCreatePartiallyPaidOrderWithAdjustment();
    }
}
