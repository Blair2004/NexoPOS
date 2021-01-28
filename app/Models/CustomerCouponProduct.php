<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerCouponProduct extends NsModel
{
    use HasFactory;
    
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
