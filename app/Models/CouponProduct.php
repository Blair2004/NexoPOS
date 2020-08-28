<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponProduct extends Model
{
    protected $table    =   'nexopos_' . 'customers_coupons_products';

    public function coupon()
    {
        return $this->belongsTo( Coupon::class );
    }

    public function product()
    {
        return $this->belongsTo( Product::class, 'product_id' );
    }
}
