<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property int $product_id
 * @property int $product_category_id
 * @property int $procurement_product_id
 * @property int $unit_id
 * @property int $unit_quantity_id
 * @property int $order_id
 * @property float $quantity
 * @property string $discount_type
 * @property float $discount
 * @property float $discount_percentage
 * @property float $gross_price
 * @property float $unit_price
 * @property int $tax_group_id
 * @property string $tax_type
 * @property int $wholesale_tax_value
 * @property float $sale_tax_value
 * @property float $tax_value
 * @property float $net_price
 * @property string $mode
 * @property string $unit_name
 * @property float $total_gross_price
 * @property float $total_price
 * @property float $total_net_price
 * @property float $total_purchase_price
 * @property string $return_condition
 * @property string $return_observations
 * @property string $uuid
 * @property int $status
 * 
 * @property Order $order
 * @property Unit $unit
 * @property Product $product
 */
class OrderProduct extends NsModel
{
    use HasFactory;

    const CONDITION_DAMAGED     =   'damaged';
    const CONDITION_UNSPOILED   =   'unspoiled';
    
    protected $table    =   'nexopos_' . 'orders_products';

    public function unit()
    {
        return $this->hasOne( Unit::class, 'id', 'unit_id' );
    }

    public function order()
    {
        return $this->belongsTo( Order::class, 'order_id', 'id' );
    }

    public function product()
    {
        return $this->hasOne( Product::class, 'id', 'product_id' );
    }

    public function scopeValidProducts( $query )
    {
        return $query->where( 'quantity', '>', 0 );
    }
}