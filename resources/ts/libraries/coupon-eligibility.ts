type CouponProductRestriction = {
    product_id?: number | string;
};

type CouponCategoryRestriction = {
    category_id?: number | string;
};

type CouponRestrictions = {
    products?: CouponProductRestriction[];
    categories?: CouponCategoryRestriction[];
};

type CouponCartProduct = {
    product_id?: number | string;
    product_category_id?: number | string;
    category_id?: number | string;
    $original?: () => {
        category_id?: number | string;
    } | undefined;
};

export function cartSatisfiesCouponProductRestrictions(
    coupon: CouponRestrictions,
    products: CouponCartProduct[]
): boolean {
    const eligibleProductIds = new Set(
        (coupon.products || []).map(restriction => Number(restriction.product_id))
    );

    if (eligibleProductIds.size === 0) {
        return true;
    }

    return products.length > 0 && products.every(product => {
        return eligibleProductIds.has(Number(product.product_id));
    });
}

export function cartSatisfiesCouponCategoryRestrictions(
    coupon: CouponRestrictions,
    products: CouponCartProduct[]
): boolean {
    const eligibleCategoryIds = new Set(
        (coupon.categories || []).map(restriction => Number(restriction.category_id))
    );

    if (eligibleCategoryIds.size === 0) {
        return true;
    }

    return products.length > 0 && products.every(product => {
        const categoryId = product.$original?.()?.category_id
            ?? product.product_category_id
            ?? product.category_id;

        return eligibleCategoryIds.has(Number(categoryId));
    });
}

export function cartSatisfiesCouponRestrictions(
    coupon: CouponRestrictions,
    products: CouponCartProduct[]
): boolean {
    return cartSatisfiesCouponProductRestrictions(coupon, products)
        && cartSatisfiesCouponCategoryRestrictions(coupon, products);
}
