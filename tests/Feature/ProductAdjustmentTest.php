<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
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
