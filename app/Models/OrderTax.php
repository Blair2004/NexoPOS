<?php

namespace App\Models;

use App\Casts\FloatConvertCasting;
use App\Events\OrderTaxAfterCreatedEvent;
use App\Events\OrderTaxBeforeCreatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int    $id
 * @property int    $order_id
 * @property float  $tax_value
 * @property string $tax_name
 */
class OrderTax extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'orders_taxes';

    public $timestamps = false;

    public $casts = [
        'tax_value' => FloatConvertCasting::class,
        'rate' => FloatConvertCasting::class,
    ];

    public $dispatchesEvents = [
        'creating' => OrderTaxBeforeCreatedEvent::class,
        'created' => OrderTaxAfterCreatedEvent::class,
    ];

    public function order()
    {
        return $this->belongsTo( Order::class, 'id', 'order_id' );
    }
}
