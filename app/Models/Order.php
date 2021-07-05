<?php
namespace App\Models;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use App\Services\DateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends NsModel
{
    use HasFactory;

    public $timestamps   =   false;
    
    protected $table    =   'nexopos_' . 'orders';

    const TYPE_DELIVERY     =   'delivery';
    const TYPE_TAKEAWAY     =   'takeaway';

    const PAYMENT_PAID                      =   'paid';
    const PAYMENT_PARTIALLY                 =   'partially_paid';
    const PAYMENT_UNPAID                    =   'unpaid';
    const PAYMENT_HOLD                      =   'hold';
    const PAYMENT_VOID                      =   'order_void';
    const PAYMENT_REFUNDED                  =   'refunded';
    const PAYMENT_PARTIALLY_REFUNDED        =   'partially_refunded';

    const PROCESSING_PENDING                =   'pending';
    const PROCESSING_ONGOING                =   'ongoing';
    const PROCESSING_READY                  =   'ready';
    const PROCESSING_FAILED                 =   'failed';

    const DELIVERY_PENDING                  =   'pending';
    const DELIVERY_ONGOING                  =   'ongoing';
    const DELIVERY_FAILED                   =   'failed';
    const DELIVERY_COMPLETED                =   'completed';

    public $casts    =   [
        'created_at'                =>  DateCast::class,
        'final_payment_date'        =>  DateCast::class,
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

    public function taxes()
    {
        return $this->hasMany( OrderTax::class, 'order_id', 'id' );
    }

    public function coupons()
    {
        return $this->hasMany( OrderCoupon::class, 'order_id', 'id' );
    }

    public function instalments()
    {
        return $this->hasMany( OrderInstalment::class, 'order_id', 'id' );
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

    public function scopePaid( $query )
    {
        return $query->where( 'payment_status', self::PAYMENT_PAID );
    }

    public function scopeRefunded( $query )
    {
        return $query->where( 'payment_status', self::PAYMENT_REFUNDED );
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
            ->where( 'final_payment_date', '<>', null )
            ->where( 'final_payment_date', '<', $date->now()->toDateTimeString() );
    }

    public function scopePaymentStatusIn( $query, array $statuses )
    {
        return $query->whereIn( 'payment_status', $statuses );
    }
}