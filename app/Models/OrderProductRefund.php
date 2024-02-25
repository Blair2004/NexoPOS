<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property int            $author
 * @property float          $total_price
 * @property string         $condition
 * @property string         $description
 * @property \Carbon\Carbon $updated_at
 */
class OrderProductRefund extends NsModel
{
    use HasFactory;

    const CONDITION_DAMAGED = 'damaged';

    const CONDITION_UNSPOILED = 'unspoiled';

    protected $table = 'nexopos_' . 'orders_products_refunds';

    public function unit()
    {
        return $this->hasOne( Unit::class, 'id', 'unit_id' );
    }

    public function product()
    {
        return $this->hasOne( Product::class, 'id', 'product_id' );
    }

    public function orderProduct()
    {
        return $this->belongsTo( OrderProduct::class, 'order_product_id', 'id' );
    }

    public function order()
    {
        return $this->belongsTo( Order::class, 'order_id', 'id' );
    }

    public function orderRefund()
    {
        return $this->belongsTo( Order::class, 'order_refund_id', 'id' );
    }
}
