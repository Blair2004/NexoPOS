<?php

namespace Tests\Feature;

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

    public function test_decrease_product_stock()
    {
        $this->attemptAuthenticate();
        $this->attemptDecreaseStockCount();
    }

    public function test_set_product_stock()
    {
        $this->attemptAuthenticate();
        $this->attemptSetStockCount();
    }

    public function test_increase_grouped_product_stock()
    {
        $this->attemptAuthenticate();
        $this->attemptGroupedProductStockAdjustment();
    }
}
