<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProductTest;

class ProductAdjustmentTest extends TestCase
{
    use WithAuthentication, WithProductTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAdjustProduct()
    {
        $this->attemptAuthenticate();
        $this->attemptAdjustmentByDeletion();
    }
}
