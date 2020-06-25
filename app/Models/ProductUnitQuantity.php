<?php
namespace App\Models;

use App\Models\Unit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProductUnitQuantity extends Model
{
    protected $table    =   'nexopos_' . 'products_unit_quantities';

    public function unit()
    {
        return $this->hasOne( Unit::class, 'id', 'unit_id' );
    }

    public function product()
    {
        return $this->hasOne( Product::class );
    }

    public function scopeFindProduct( Builder $query, $id )
    {
        return $query->where( 'product_id', $id );
    }
}