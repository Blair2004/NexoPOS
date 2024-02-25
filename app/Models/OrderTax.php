<?php

namespace App\Models;

use App\Casts\FloatConvertCasting;
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

    public function order()
    {
        return $this->belongsTo( Order::class, 'id', 'order_id' );
    }
}
