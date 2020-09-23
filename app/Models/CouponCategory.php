<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CouponCategory extends Model
{
    use HasFactory;

    protected $table    =   'nexopos_' . 'customers_coupons_categories';

    public function coupon()
    {
        return $this->belongsTo( Coupon::class, 'coupon_id' );
    }

    public function product()
    {
        return $this->belongsTo( Product::class, 'product_id' );
    }
}
