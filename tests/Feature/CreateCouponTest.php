<?php

namespace Tests\Feature;

use App\Models\Coupon;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCouponTest;

class CreateCouponTest extends TestCase
{
    use WithAuthentication, WithCouponTest;

    public function test_percentage_discount_cannot_exceed_one_hundred_percent(): void
    {
        $this->attemptAuthenticate();

        $couponCount = Coupon::count();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.coupons', [
                'name' => 'Invalid Percentage Coupon',
                'general' => [
                    'type' => 'percentage_discount',
                    'code' => 'invalid-percentage-' . uniqid(),
                    'discount_value' => 101,
                    'limit_usage' => 10,
                ],
            ] );

        $response->assertForbidden()->assertJsonPath( 'status', 'error' );
        $this->assertSame( $couponCount, Coupon::count() );
    }

    public function test_limit_usage_cannot_be_negative(): void
    {
        $this->attemptAuthenticate();

        $couponCount = Coupon::count();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.coupons', [
                'name' => 'Invalid Usage Coupon',
                'general' => [
                    'type' => 'flat_discount',
                    'code' => 'invalid-usage-' . uniqid(),
                    'discount_value' => 10,
                    'limit_usage' => -1,
                ],
            ] );

        $response->assertStatus( 422 )
            ->assertJsonValidationErrors( 'general.limit_usage' );

        $this->assertSame( $couponCount, Coupon::count() );
    }

    public function test_minimum_cart_value_cannot_exceed_maximum_cart_value(): void
    {
        $this->attemptAuthenticate();

        $couponCount = Coupon::count();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.coupons', [
                'name' => 'Invalid Cart Range Coupon',
                'general' => [
                    'type' => 'flat_discount',
                    'code' => 'invalid-range-' . uniqid(),
                    'discount_value' => 10,
                    'minimum_cart_value' => 100,
                    'maximum_cart_value' => 50,
                    'limit_usage' => 10,
                ],
            ] );

        $response->assertForbidden()->assertJsonPath( 'status', 'error' );
        $this->assertSame( $couponCount, Coupon::count() );
    }

    public function test_flattened_negative_values_are_rejected(): void
    {
        $this->attemptAuthenticate();

        $couponCount = Coupon::count();
        $negativeValues = [
            'discount_value' => -5,
            'minimum_cart_value' => -1,
            'maximum_cart_value' => -1,
            'limit_usage' => -1,
        ];

        foreach ( $negativeValues as $field => $value ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/crud/ns.coupons', [
                    'name' => 'Invalid Negative Coupon ' . $field,
                    $field => $value,
                    'general' => [
                        'type' => 'flat_discount',
                        'code' => 'invalid-negative-' . $field . '-' . uniqid(),
                        'discount_value' => 10,
                        'minimum_cart_value' => 0,
                        'maximum_cart_value' => 0,
                        'limit_usage' => 10,
                    ],
                ] );

            $response->assertForbidden()->assertJsonPath( 'status', 'error' );
        }

        $this->assertSame( $couponCount, Coupon::count() );
    }

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
