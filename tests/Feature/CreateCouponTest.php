<?php

namespace Tests\Feature;

use App\Models\Role;
use Carbon\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductCategory;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCouponTest;

class CreateCouponTest extends TestCase
{
    use WithAuthentication, WithCouponTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateCoupon()
    {
        $this->attemptAuthenticate();
        $this->attemptCreatecoupon();
    }

    /**
     * Let's now try to update the coupon.
     * 
     * @return void
     */
    public function testUpdateCoupon()
    {
        $this->attemptAuthenticate();
        $this->attemptUpdateCoupon();
    }
}
