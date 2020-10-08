<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'orders';

    const TYPE_DELIVERY     =   'delivery';
    const TYPE_TAKEAWAY     =   'takeaway';

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
}