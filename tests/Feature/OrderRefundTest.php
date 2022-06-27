<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class OrderRefundTest extends TestCase
{
    use WithOrderTest, WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testRefund()
    {
        $this->attemptAuthenticate();
        $this->attemptRefundOrder();
    }
}
