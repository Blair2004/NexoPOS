<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property string $type
 * @property float $limit_usage
 * @property \Carbon\Carbon $updated_at
 * @property integer $author
*/
class Coupon extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'coupons';

    public function categories()
    {
        return $this->hasMany( CouponCategory::class );
    }

    public function products()
    {
        return $this->hasMany( CouponProduct::class );
    }
}
