<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\RewardSystem;
use App\Models\Role;
use App\Models\User;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
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
