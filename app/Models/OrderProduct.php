<?php

namespace App\Models;

use App\Casts\FloatConvertCasting;
use App\Events\OrderProductAfterCreatedEvent;
use App\Events\OrderProductAfterUpdatedEvent;
use App\Events\OrderProductBeforeCreatedEvent;
use App\Events\OrderProductBeforeUpdatedEvent;
use App\Traits\NsFiltredAttributes;
use App\Traits\NsFlashData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int     $id
 * @property string  $name
 * @property int     $product_id
 * @property int     $product_category_id
 * @property int     $procurement_product_id
 * @property int     $unit_id
 * @property int     $unit_quantity_id
 * @property int     $order_id
 * @property float   $quantity
 * @property string  $discount_type
 * @property float   $discount
 * @property float   $discount_percentage
 * @property float   $price_without_tax
 * @property float   $unit_price
 * @property int     $tax_group_id
 * @property string  $tax_type
 * @property int     $wholesale_tax_value
 * @property string  $mode
 * @property float   $sale_tax_value
 * @property float   $tax_value
 * @property float   $price_with_tax
 * @property string  $unit_name
 * @property float   $total_price_without_tax
 * @property float   $total_price
 * @property float   $total_price_with_tax
 * @property float   $total_purchase_price
 * @property string  $return_condition
 * @property string  $return_observations
 * @property string  $uuid
 * @property int     $status
 * @property Order   $order
 * @property Unit    $unit
 * @property Product $product
 */
class OrderProduct extends NsModel
{
    use HasFactory, NsFiltredAttributes, NsFlashData;

    const CONDITION_DAMAGED = 'damaged';

    const CONDITION_UNSPOILED = 'unspoiled';

    protected $table = 'nexopos_' . 'orders_products';

    protected $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'product_category_id' => 'integer',
        'procurement_product_id' => 'integer',
        'unit_id' => 'integer',
        'unit_quantity_id' => 'integer',
        'order_id' => 'integer',
        'tax_group_id' => 'integer',
        'quantity' => FloatConvertCasting::class,
        'discount' => FloatConvertCasting::class,
        'discount_percentage' => FloatConvertCasting::class,
        'price_without_tax' => FloatConvertCasting::class,
        'unit_price' => FloatConvertCasting::class,
        'sale_tax_value' => FloatConvertCasting::class,
        'tax_value' => FloatConvertCasting::class,
        'price_with_tax' => FloatConvertCasting::class,
        'total_price_without_tax' => FloatConvertCasting::class,
        'total_price' => FloatConvertCasting::class,
        'total_price_with_tax' => FloatConvertCasting::class,
        'total_purchase_price' => FloatConvertCasting::class,
    ];

    public $dispatchesEvents = [
        'creating' => OrderProductBeforeCreatedEvent::class,
        'created' => OrderProductAfterCreatedEvent::class,
        'updated' => OrderProductAfterUpdatedEvent::class,
        'updating' => OrderProductBeforeUpdatedEvent::class,
    ];

    public function tax_group()
    {
        return $this->hasOne( TaxGroup::class, 'id', 'tax_group_id' );
    }

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

    public function refunded_products()
    {
        return $this->hasMany(
            related: OrderProductRefund::class,
            foreignKey: 'order_product_id',
            localKey: 'id'
        );
    }

    public function scopeValidProducts( $query )
    {
        return $query->where( 'quantity', '>', 0 );
    }
}
