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
    public function testCreateProductWithCustomPriceMode()
    {
        $this->attemptAuthenticate();
        $this->attemptOrderWithProductPriceMode();
    }
}
