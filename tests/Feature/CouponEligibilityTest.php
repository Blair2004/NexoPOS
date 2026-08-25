<?php

namespace Tests\Feature;

use App\Exceptions\NotAllowedException;
use App\Models\Coupon;
use App\Models\CouponCategory;
use App\Models\CouponProduct;
use App\Models\Product;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CouponEligibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_product_restricted_coupon_accepts_only_eligible_products(): void
    {
        $coupon = $this->createCoupon();
        $eligibleProduct = $this->makeProduct( 1001, 2001 );

        $this->attachProductRestriction( $coupon, $eligibleProduct );

        $result = app( CustomerService::class )->checkCouponExistence(
            [ 'coupon_id' => $coupon->id ],
            [ 'products' => [
                [ 'product' => $eligibleProduct, 'quantity' => 2 ],
                [ 'product' => $eligibleProduct, 'quantity' => 1 ],
            ] ]
        );

        $this->assertTrue( $coupon->is( $result ) );
    }

    public function test_product_restricted_coupon_rejects_any_ineligible_product(): void
    {
        $coupon = $this->createCoupon();
        $eligibleProduct = $this->makeProduct( 1002, 2002 );
        $ineligibleProduct = $this->makeProduct( 1003, 2002 );

        $this->attachProductRestriction( $coupon, $eligibleProduct );

        $this->expectException( NotAllowedException::class );

        app( CustomerService::class )->checkCouponExistence(
            [ 'coupon_id' => $coupon->id ],
            [ 'products' => [
                [ 'product' => $eligibleProduct ],
                [ 'product' => $ineligibleProduct ],
            ] ]
        );
    }

    public function test_product_restricted_coupon_rejects_an_empty_cart(): void
    {
        $coupon = $this->createCoupon();
        $eligibleProduct = $this->makeProduct( 1004, 2003 );

        $this->attachProductRestriction( $coupon, $eligibleProduct );
        $this->expectException( NotAllowedException::class );

        app( CustomerService::class )->checkCouponExistence(
            [ 'coupon_id' => $coupon->id ],
            [ 'products' => [] ]
        );
    }

    public function test_category_restricted_coupon_rejects_any_ineligible_category(): void
    {
        $coupon = $this->createCoupon();
        $eligibleProduct = $this->makeProduct( 1005, 2004 );
        $ineligibleProduct = $this->makeProduct( 1006, 2005 );

        $couponCategory = new CouponCategory;
        $couponCategory->coupon_id = $coupon->id;
        $couponCategory->category_id = $eligibleProduct->category_id;
        $couponCategory->save();

        $result = app( CustomerService::class )->checkCouponExistence(
            [ 'coupon_id' => $coupon->id ],
            [ 'products' => [
                [ 'product' => $eligibleProduct ],
            ] ]
        );

        $this->assertTrue( $coupon->is( $result ) );
        $this->expectException( NotAllowedException::class );

        app( CustomerService::class )->checkCouponExistence(
            [ 'coupon_id' => $coupon->id ],
            [ 'products' => [
                [ 'product' => $eligibleProduct ],
                [ 'product' => $ineligibleProduct ],
            ] ]
        );
    }

    public function test_category_restricted_coupon_rejects_an_empty_cart(): void
    {
        $coupon = $this->createCoupon();
        $eligibleProduct = $this->makeProduct( 1009, 2008 );

        $this->attachCategoryRestriction( $coupon, $eligibleProduct->category_id );
        $this->expectException( NotAllowedException::class );

        app( CustomerService::class )->checkCouponExistence(
            [ 'coupon_id' => $coupon->id ],
            [ 'products' => [] ]
        );
    }

    public function test_coupon_with_product_and_category_restrictions_accepts_only_their_intersection(): void
    {
        $coupon = $this->createCoupon();
        $eligibleProduct = $this->makeProduct( 1010, 2009 );
        $productWithIneligibleCategory = $this->makeProduct( 1011, 2010 );

        $this->attachProductRestriction( $coupon, $eligibleProduct );
        $this->attachProductRestriction( $coupon, $productWithIneligibleCategory );
        $this->attachCategoryRestriction( $coupon, $eligibleProduct->category_id );

        $result = app( CustomerService::class )->checkCouponExistence(
            [ 'coupon_id' => $coupon->id ],
            [ 'products' => [ [ 'product' => $eligibleProduct ] ] ]
        );

        $this->assertTrue( $coupon->is( $result ) );
        $this->expectException( NotAllowedException::class );

        app( CustomerService::class )->checkCouponExistence(
            [ 'coupon_id' => $coupon->id ],
            [ 'products' => [ [ 'product' => $productWithIneligibleCategory ] ] ]
        );
    }

    public function test_unrestricted_coupon_accepts_any_products(): void
    {
        $coupon = $this->createCoupon();

        $result = app( CustomerService::class )->checkCouponExistence(
            [ 'coupon_id' => $coupon->id ],
            [ 'products' => [
                [ 'product' => $this->makeProduct( 1007, 2006 ) ],
                [ 'product' => $this->makeProduct( 1008, 2007 ) ],
            ] ]
        );

        $this->assertTrue( $coupon->is( $result ) );
    }

    private function createCoupon(): Coupon
    {
        return Coupon::factory()->create( [ 'author_id' => 1 ] );
    }

    private function attachProductRestriction( Coupon $coupon, Product $product ): void
    {
        $couponProduct = new CouponProduct;
        $couponProduct->coupon_id = $coupon->id;
        $couponProduct->product_id = $product->id;
        $couponProduct->save();
    }

    private function attachCategoryRestriction( Coupon $coupon, int $categoryId ): void
    {
        $couponCategory = new CouponCategory;
        $couponCategory->coupon_id = $coupon->id;
        $couponCategory->category_id = $categoryId;
        $couponCategory->save();
    }

    private function makeProduct( int $productId, int $categoryId ): Product
    {
        $product = new Product;
        $product->id = $productId;
        $product->category_id = $categoryId;

        return $product;
    }
}
