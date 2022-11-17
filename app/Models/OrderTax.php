<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property integer $order_id
 * @property float $tax_value
 * @property string $tax_name
*/
class OrderTax extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'orders_taxes';

    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo( Order::class, 'id', 'order_id' );
    }
}
