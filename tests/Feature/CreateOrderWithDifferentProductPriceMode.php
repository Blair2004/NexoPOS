<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
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
