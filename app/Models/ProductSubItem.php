<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $parent_id
 * @property int    $product_id
 * @property int    $unit_id
 * @property int    $unit_quantity_id
 * @property float  $quantity
 * @property float  $sale_price
 * @property float  $total_price
 * @property int    $author
 * @property string $created_at
 * @property string $updated_at
 */
class ProductSubItem extends Model
{
    use HasFactory;

    protected $table = 'nexopos_products_subitems';

    public function unit()
    {
        return $this->hasOne( Unit::class, 'id', 'unit_id' );
    }

    public function product()
    {
        return $this->hasOne( Product::class, 'id', 'product_id' );
    }

    public function parent()
    {
        return $this->hasOne( Product::class, 'id', 'parent_id' );
    }

    public function unit_quantity()
    {
        return $this->hasOne( ProductUnitQuantity::class, 'id', 'unit_quantity_id' );
    }
}
