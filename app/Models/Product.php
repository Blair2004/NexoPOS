<?php
namespace App\Models;

use App\ProductGallery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    const STOCK_MANAGEMENT_ENABLED      =   'enabled';
    const STOCK_MANAGEMENT_DISABLED     =   'disabled';
    
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
     * get a product using a barcode
     * @param QueryBuilder
     * @param string barcode
     * @return QueryBuilder
     */
    public function scopeBarcode( $query, $barcode )
    {
        return $this->scopeFindUsingBarcode( $query, $barcode );
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

    public function product_taxes()
    {
        return $this->hasMany( ProductTax::class, 'product_id' );
    }

    public function variations()
    {
        return $this->hasMany( Product::class, 'parent_id' );
    }

    public function galleries()
    {
        return $this->hasMany( ProductGallery::class, 'product_id', 'id' );
    }

    public function scopeOnlyVariations( $query )
    {
        return $query->where( 'product_type', 'variation' );
    }

    public function scopeExcludeVariations( $query )
    {
        return $query->where( 'product_type', '!=', 'variation' );
    }

    public function scopeWithStockEnabled( $query )
    {
        return $query->where( 'stock_management', Product::STOCK_MANAGEMENT_ENABLED );
    }

    public function scopeWithStockDisabled( $query )
    {
        return $query->where( 'stock_management', Product::STOCK_MANAGEMENT_DISABLED );
    }
}