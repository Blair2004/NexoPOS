<?php
namespace App\Models;

use App\Casts\DateCast;
use App\Services\DateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'orders';

    const TYPE_DELIVERY     =   'delivery';
    const TYPE_TAKEAWAY     =   'takeaway';

    const PAYMENT_PAID          =   'paid';
    const PAYMENT_PARTIALLY     =   'partially_paid';
    const PAYMENT_UNPAID        =   'unpaid';
    const PAYMENT_HOLD          =   'hold';
    const PAYMENT_VOID          =   'order_void';
    const PAYMENT_REFUNDED      =   'refunded';

    public $casts    =   [
        'created_at'    =>  DateCast::class
    ];

    public function products()
    {
        return $this->hasMany(
            OrderProduct::class,
            'order_id'
        );
    }

    public function user()
    {
        return $this->hasOne( User::class, 'id', 'author' );
    }

    public function refund()
    {
        return $this->hasMany( OrderRefund::class, 'order_id', 'id' );
    }

    public function payments()
    {
        return $this->hasMany( OrderPayment::class, 'order_id' );
    }

    public function customer()
    {
        return $this->hasOne( Customer::class, 'id', 'customer_id' );
    }

    public function shipping_address()
    {
        return $this->hasOne( OrderShippingAddress::class );
    }

    public function billing_address()
    {
        return $this->hasOne( OrderBillingAddress::class );
    }

    public function scopeFrom( $query, $range_starts )
    {
        return $query->where( 'created_at', '>=', $range_starts );
    }

    public function scopeTo( $query, $range_ends )
    {
        return $query->where( 'created_at', '<=', $range_ends );
    }

    public function scopePaymentStatus( $query, $status )
    {
        return $query->where( 'payment_status', $status );
    }

    public function scopePaymentExpired( $query )
    {
        $date   =   app()->make( DateService::class );

        return $query
            ->whereIn( 'payment_status', [ Order::PAYMENT_PARTIALLY, Order::PAYMENT_UNPAID ])
            ->where( 'expected_payment_date', '<>', null )
            ->where( 'expected_payment_date', '<', $date->now()->toDateTimeString() );
    }
}