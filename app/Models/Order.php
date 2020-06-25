<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table    =   'nexopos_' . 'orders';

    public function products()
    {
        return $this->hasMany(
            OrderProduct::class,
            'order_id'
        );
    }

    public function payments()
    {
        return $this->hasMany( OrderPayment::class, 'order_id' );
    }
}