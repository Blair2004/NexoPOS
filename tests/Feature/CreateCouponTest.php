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
    public function test_create_coupon()
    {
        $this->attemptAuthenticate();
        $this->attemptCreatecoupon();
    }

    /**
     * Let's now try to update the coupon.
     *
     * @return void
     */
    public function test_update_coupon()
    {
        $this->attemptAuthenticate();
        $this->attemptUpdateCoupon();
    }

    /**
     * We'll try to use coupon and track usage
     */
    public function test_track_coupon_usage()
    {
        $this->attemptAuthenticate();
        $this->attemptAssignCouponToOrder();
    }

    /**
     * This test will assign a coupon that doesn't exist
     * to an order. This will cause the order to throw an error.
     */
    public function test_assign_not_existing_coupon()
    {
        $this->attemptAuthenticate();
        $this->attemptAssigningANonExistingCoupon();
    }

    /**
     * This test will try to assign a coupon
     * that is exhausted. It should cause a failure of the order creation
     */
    public function test_use_exhausted_coupon()
    {
        $this->attemptAuthenticate();
        $this->attemptUseExaustedCoupon();
    }

    /**
     * This test will use coupon this it get exhausted.
     * By the end, the order creation should fail.
     */
    public function test_use_coupon_till_usage_get_exhausted()
    {
        $this->attemptAuthenticate();
        $this->attemptUseCouponTillUsageIsExhausted();
    }
}
