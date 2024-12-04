<?php

namespace App\Models;

use App\Events\OrderCouponAfterCreatedEvent;
use App\Events\OrderCouponAfterUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property string         $uuid
 * @property int            $author
 * @property float          $value
 * @property \Carbon\Carbon $updated_at
 */
class OrderCoupon extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'orders_coupons';

    public $dispatchesEvents = [
        'created' => OrderCouponAfterCreatedEvent::class,
        'updated' => OrderCouponAfterUpdatedEvent::class,
    ];

    public function order()
    {
        return $this->belongsTo( Order::class, 'order_id' );
    }

    public function customerCoupon()
    {
        return $this->belongsTo( CustomerCoupon::class, 'customer_coupon_id' );
    }
}
