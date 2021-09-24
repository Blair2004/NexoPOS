<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerCoupon extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'customers_coupons';

    public $casts    =   [
        'active'    =>  'boolean'
    ];

    public function scopeActive( $query )
    {
        return $query->where( 'active', true );
    }

    public function scopeCode( $query, $code )
    {
        return $query->where( 'code', $code );
    }

    public function scopeCouponID( $query, $couponID )
    {
        return $query->where( 'coupon_id', $couponID );
    }

    public function scopeCustomer( $query, $customer_id )
    {
        return $query->where( 'customer_id', $customer_id );
    }

    public function coupon() {
        return $this->hasOne( Coupon::class, 'id', 'coupon_id' );
    }

    public function customer()
    {
        return $this->belongsTo( Customer::class, 'customer_id', 'id' );
    }
}
