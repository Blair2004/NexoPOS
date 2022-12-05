<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

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
