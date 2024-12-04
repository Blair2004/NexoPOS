<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateOrderWithDifferentProductPriceMode extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_product_with_custom_price_mode()
    {
        $this->attemptAuthenticate();
        $this->attemptOrderWithProductPriceMode();
    }
}
