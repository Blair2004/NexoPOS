<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int    $id
 * @property int    $author_id
 * @property float  $shipping
 * @property string $payment_method
 * @property Carbon $updated_at
 */
class OrderRefund extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'orders_refunds';

    public function refunded_products()
    {
        return $this->hasMany( OrderProductRefund::class, 'order_refund_id', 'id' );
    }

    public function order()
    {
        return $this->belongsTo( Order::class, 'order_id', 'id' );
    }

    public function author()
    {
        return $this->belongsTo( User::class );
    }
}
