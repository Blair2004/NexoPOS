<?php
namespace App\Models;

use App\Casts\DateCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'orders';

    const TYPE_DELIVERY     =   'delivery';
    const TYPE_TAKEAWAY     =   'takeaway';

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
}