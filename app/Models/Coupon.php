<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property mixed $name
 * @property mixed $code
 * @property mixed $type
 * @property float $discount_value
 * @property \Carbon\Carbon $valid_until
 * @property float $minimum_cart_value
 * @property float $maximum_cart_value
 * @property \Carbon\Carbon $valid_hours_start
 * @property \Carbon\Carbon $valid_hours_end
 * @property float $limit_usage
 * @property integer $author
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
*/
class Coupon extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'coupons';

    const TYPE_PERCENTAGE   =   'percentage_discount';
    const TYPE_FLAT         =   'flat_discount';

    public function categories()
    {
        return $this->hasMany( CouponCategory::class );
    }

    public function products()
    {
        return $this->hasMany( CouponProduct::class );
    }
}
