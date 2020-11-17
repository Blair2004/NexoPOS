<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderRefund extends Model
{
    use HasFactory;

    protected $table    =   'nexopos_' . 'orders_refunds';
    
    public function refunded_products()
    {
        return $this->hasMany( OrderProductRefund::class, 'refund_order_id', 'id' );
    }

    public function order()
    {
        return $this->belongsTo( Order::class, 'order_id', 'id' );
    }
}