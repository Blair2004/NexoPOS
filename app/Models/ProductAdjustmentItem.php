<?php

namespace App\Models;

/**
 * @property int            $id
 * @property int            $adjustment_id
 * @property int            $product_id
 * @property string         $product_name
 * @property int            $unit_id
 * @property string         $unit_name
 * @property float          $unit_price
 * @property float          $quantity
 * @property string         $adjust_action
 * @property string|null    $description
 * @property int|null       $procurement_product_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductAdjustmentItem extends NsModel
{
    protected $table = 'nexopos_' . 'products_adjustment_items';

    protected $fillable = [
        'adjustment_id',
        'product_id',
        'product_name',
        'unit_id',
        'unit_name',
        'unit_price',
        'quantity',
        'adjust_action',
        'description',
        'procurement_product_id',
    ];

    public function adjustment()
    {
        return $this->belongsTo( ProductAdjustment::class, 'adjustment_id' );
    }
}
