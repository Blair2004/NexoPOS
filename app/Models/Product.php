<?php
namespace App\Models;

use App\Models\ProductGallery;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property string $name
 * @property string $tax_type
 * @property int $tax_group_id
 * @property float $tax_value
 * @property string $product_type
 * @property string $type
 * @property string $accurate_tracking
 * @property string $status
 * @property string $stock_management Can either be "enabled" or "disabled". Use Product::STOCK_MANAGEMEND_ENABLED or Product::STOCK_MANAGEMENT_DISABLED for accessing those values.
 * @property string $barcode
 * @property string $barcode_type
 * @property string $sku
 * @property string $description
 * @property int $thumbnail_id
 * @property int $category_id
 * @property int $parent_id
 * @property int $unit_group
 * @property string $on_expiration
 * @property boolean $expires wether or not the product has expired
 * @property boolean $searchable 
 * @property int $author
 * @property string $uuid
 */
class Product extends NsModel
{
    use HasFactory;

    const STOCK_MANAGEMENT_ENABLED      =   'enabled';
    const STOCK_MANAGEMENT_DISABLED     =   'disabled';

    const EXPIRES_PREVENT_SALES         =   'prevent_sales';
    const EXPIRES_ALLOW_SALES           =   'allow_sales';
    
    protected $table                    =   'nexopos_' . 'products';
    protected $cats                     =   [
        'accurate_tracking'     =>  'boolean'
    ];

    public function category()
    {
        return $this->belongsTo( ProductCategory::class, 'category_id', 'id' );
    }

    /**
     * get products having accurate tracking enabled
     * @param QueryBuilder
     * @return QueryBuilder
     */
    public function scopeTrackingEnabled( $query )
    {
        return $query->where( 'accurate_tracking', true );
    }

    /**
     * get products having accurate tracking disabled
     * @param QueryBuilder
     * @return QueryBuilder
     */
    public function scopeTrackingDisabled( $query )
    {
        return $query->where( 'accurate_tracking', false );
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

    public function procurementHistory()
    {
        return $this->hasMany( ProcurementProduct::class, 'product_id', 'id' );
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
     * Filter query by getitng product with
     * accurate stock enabled or not.
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    public function scopeAccurateTracking( $query, $argument = true )
    {
        return $query->where( 'accurate_tracking', $argument );
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