<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property mixed          $name
 * @property mixed          $code
 * @property mixed          $type
 * @property float          $discount_value
 * @property \Carbon\Carbon $valid_until
 * @property float          $minimum_cart_value
 * @property float          $maximum_cart_value
 * @property \Carbon\Carbon $valid_hours_start
 * @property \Carbon\Carbon $valid_hours_end
 * @property float          $limit_usage
 * @property int            $author
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Coupon extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'coupons';

    const TYPE_PERCENTAGE = 'percentage_discount';

    const TYPE_FLAT = 'flat_discount';

    public function scopeCode( $query, $code )
    {
        return $query->where( 'code', $code );
    }

    public function customerCoupon()
    {
        return $this->hasMany(
            related: CustomerCoupon::class,
            foreignKey: 'coupon_id',
            localKey: 'id'
        );
    }

    public function categories()
    {
        return $this->hasMany( CouponCategory::class );
    }

    public function products()
    {
        return $this->hasMany( CouponProduct::class );
    }

    public function customers()
    {
        return $this->hasMany(
            related: CouponCustomer::class,
            foreignKey: 'coupon_id',
            localKey: 'id'
        );
    }

    public function groups()
    {
        return $this->hasMany(
            related: CouponCustomerGroup::class,
            foreignKey: 'coupon_id',
            localKey: 'id'
        );
    }
}
