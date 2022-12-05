<?php

namespace Tests\Feature;

use Tests\TestCase;
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

    /**
     * We'll try to use coupon and track usage
     */
    public function testTrackCouponUsage()
    {
        $this->attemptAuthenticate();
        $this->attemptAssignCouponToOrder();
    }
}
