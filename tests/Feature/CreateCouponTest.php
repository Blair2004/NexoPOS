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

    /**
     * This test will assign a coupon that doesn't exist
     * to an order. This will cause the order to throw an error.
     */
    public function testAssignNotExistingCoupon()
    {
        $this->attemptAuthenticate();
        $this->attemptAssigningANonExistingCoupon();
    }

    /**
     * This test will try to assign a coupon
     * that is exhausted. It should cause a failure of the order creation
     */
    public function testUseExhaustedCoupon()
    {
        $this->attemptAuthenticate();
        $this->attemptUseExaustedCoupon();
    }

    /**
     * This test will use coupon this it get exhausted.
     * By the end, the order creation should fail.
     */
    public function testUseCouponTillUsageGetExhausted()
    {
        $this->attemptAuthenticate();
        $this->attemptUseCouponTillUsageIsExhausted();
    }
}
