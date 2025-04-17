<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDeliveryProof extends Model
{
    public $table = 'nexopos_orders_delivery_proof';

    protected $fillable = [
        'is_delivered',
        'note',
        'delivery_proof',
        'paid_on_delivery',
        'order_id',
        'driver_id',
    ];

    protected $casts = [
        'is_delivered' => 'boolean',
        'paid_on_delivery' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
