<?php
namespace App\Models;

use App\Models\ProductGallery;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends NsModel
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
     * get a product using a barcode
     * @param QueryBuilder
     * @param string barcode
     * @return QueryBuilder
     */
    public function scopeSku( $query, $sku )
    {
        return $this->scopeFindUsingSKU( $query, $sku );
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

    public function unitGroup()
    {
        return $this->hasOne( UnitGroup::class, 'id', 'unit_group' );
    }

    public function product_taxes()
    {
        return $this->hasMany( ProductTax::class, 'product_id' );
    }

    public function tax_group()
    {
        return $this->hasOne( TaxGroup::class, 'id', 'tax_group_id' );
    }

    public function variations()
    {
        return $this->hasMany( Product::class, 'parent_id' );
    }

    public function galleries()
    {
        return $this->hasMany( ProductGallery::class, 'product_id', 'id' );
    }

    /**
     * Filter query by getting products that are variations
     * @param QueryBuilder $query
     * @return QueryBuilder;
     */
    public function scopeOnlyVariations( $query )
    {
        return $query->where( 'product_type', 'variation' );
    }

    /**
     * Filter query by getting products that aren't variations
     * @param QueryBuilder $query
     * @return QueryBuilder;
     */
    public function scopeExcludeVariations( $query )
    {
        return $query->where( 'product_type', '!=', 'variation' );
    }

    /**
     * Filter query by getting product with
     * stock management enabled
     * @param QueryBuilder $query
     * @return QueryBuilder;
     */
    public function scopeWithStockEnabled( $query )
    {
        return $query->where( 'stock_management', Product::STOCK_MANAGEMENT_ENABLED );
    }

    /**
     * Filter query by getting product with
     * stock management disabled
     * @param QueryBuilder $query
     * @return QueryBuilder;
     */
    public function scopeWithStockDisabled( $query )
    {
        return $query->where( 'stock_management', Product::STOCK_MANAGEMENT_DISABLED );
    }

    /**
     * Filter product that are searchable
     * @param QueryBuilder 
     * @return QueryBuilder
     */
    public function scopeSearchable( $query, $attribute = true )
    {
        return $query->where( 'searchable', $attribute );
    }
}