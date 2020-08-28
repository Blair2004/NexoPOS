<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table    =   'nexopos_' . 'products';

    public function category()
    {
        return $this->belongsTo( ProductCategory::class, 'category_id', 'id' );
    }

    /**
     * get a product using a barcode
     * @param QueryBuilder
     * @param string barcode
     * @return QueryBuilder
     */
    public function scopeFindUsingBarcode( $query, $barcode )
    {
        return $query->where( 'barcode', $barcode );
    }


    /**
     * Filter a product using the SKU
     * @param QueryBuilder
     * @param string sku
     * @return QueryBuilder
     */
    public function scopeFindUsingSKU( $query, $sku )
    {
        return $query->where( 'sku', $sku );
    }

    public function unit_quantities()
    {
        return $this->hasMany( ProductUnitQuantity::class, 'product_id' );
    }

    public function variations()
    {
        return $this->hasMany( Product::class, 'parent_id' );
    }

    public function scopeOnlyVariations( $query )
    {
        return $query->where( 'product_type', 'variation' );
    }

    public function scopeExcludeVariations( $query )
    {
        return $query->where( 'product_type', '!=', 'variation' );
    }
}