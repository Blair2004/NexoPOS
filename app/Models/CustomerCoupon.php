<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * This class is made to ensure that NexoPOS can count coupon usage
 * for a specific customer. Additionnaly when a reward system issue a coupon for a customer, it creates an instance
 * of this class which can there after be used by the customer.
 */
/**
 * @property int            $id
 * @property string         $code
 * @property int            $author
 * @property \Carbon\Carbon $updated_at
 * @property bool           $active
 */
class CustomerCoupon extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'customers_coupons';

    public $casts = [
        'active' => 'boolean',
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

    public function coupon()
    {
        return $this->hasOne( Coupon::class, 'id', 'coupon_id' );
    }

    public function customer()
    {
        return $this->belongsTo( Customer::class, 'customer_id', 'id' );
    }
}
