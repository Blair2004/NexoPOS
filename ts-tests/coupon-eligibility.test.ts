import assert from 'node:assert/strict';
import { describe, test } from 'node:test';

import {
    cartSatisfiesCouponCategoryRestrictions,
    cartSatisfiesCouponProductRestrictions,
    cartSatisfiesCouponRestrictions,
} from '../resources/ts/libraries/coupon-eligibility.ts';

describe('Coupon cart eligibility', () => {
    const eligibleProduct = {
        product_id: 10,
        $original: () => ({ category_id: 20 }),
    };

    test('allows unrestricted coupons', () => {
        assert.equal(cartSatisfiesCouponRestrictions({}, [eligibleProduct]), true);
    });

    test('allows a cart containing only eligible products', () => {
        const coupon = { products: [{ product_id: 10 }] };

        assert.equal(cartSatisfiesCouponProductRestrictions(coupon, [eligibleProduct, eligibleProduct]), true);
    });

    test('rejects the coupon when any product is ineligible', () => {
        const coupon = { products: [{ product_id: 10 }] };
        const ineligibleProduct = { product_id: 11 };

        assert.equal(cartSatisfiesCouponProductRestrictions(coupon, [eligibleProduct, ineligibleProduct]), false);
    });

    test('rejects a restricted coupon for an empty cart', () => {
        const coupon = { products: [{ product_id: 10 }] };

        assert.equal(cartSatisfiesCouponProductRestrictions(coupon, []), false);
    });

    test('requires every product to belong to an eligible category', () => {
        const coupon = { categories: [{ category_id: 20 }] };
        const ineligibleProduct = {
            product_id: 11,
            $original: () => ({ category_id: 21 }),
        };

        assert.equal(cartSatisfiesCouponCategoryRestrictions(coupon, [eligibleProduct]), true);
        assert.equal(cartSatisfiesCouponCategoryRestrictions(coupon, [eligibleProduct, ineligibleProduct]), false);
    });
});
