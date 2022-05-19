<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
