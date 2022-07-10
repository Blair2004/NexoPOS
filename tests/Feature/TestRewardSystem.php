<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class TestRewardSystem extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_reward_system()
    {
        $this->attemptAuthenticate();
        $this->attemptTestRewardSystem();
    }

    public function test_coupon_usage()
    {
        $this->attemptAuthenticate();
        $this->attemptCouponUsage();
    }
}
