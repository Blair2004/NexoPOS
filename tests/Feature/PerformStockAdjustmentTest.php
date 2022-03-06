<?php

namespace Tests\Feature;

use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProductTest;

class PerformStockAdjustmentTest extends TestCase
{
    use WithAuthentication, WithProductTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_increase_product_stock()
    {
        $this->attemptAuthenticate();
        $this->attemptProductStockAdjustment();
    }

    public function test_decreate_product_stock()
    {
        $this->attemptAuthenticate();
        $this->attemptDecreaseStockCount();
    }
}
