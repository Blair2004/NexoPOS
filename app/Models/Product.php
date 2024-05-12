<?php

namespace App\Models;

use App\Events\ProductAfterDeleteEvent;
use App\Events\ProductBeforeDeleteEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string   $id
 * @property string   $name
 * @property string   $tax_type
 * @property int      $tax_group_id
 * @property float    $tax_value
 * @property string   $product_type
 * @property string   $type
 * @property bool     $accurate_tracking
 * @property bool     $auto_cogs
 * @property string   $status
 * @property string   $stock_management  Can either be "enabled" or "disabled"
 * @property string   $barcode
 * @property string   $barcode_type
 * @property string   $sku
 * @property string   $description
 * @property int      $thumbnail_id
 * @property int      $category_id
 * @property int      $parent_id
 * @property int      $unit_group
 * @property string   $on_expiration
 * @property bool     $expires           whether or not the product has expired
 * @property bool     $searchable
 * @property int      $author
 * @property string   $uuid
 * @property TaxGroup $tax_group
 *
 * @method static Builder trackingEnabled()
 * @method static Builder trackingDisabled()
 * @method static Builder findUsingBarcode( $barcode )
 * @method static Builder barcode( $barcode )
 * @method static Builder sku( $sku )
 * @method static Builder onSale()
 * @method static Builder hidden()
 * @method static Builder findUsingSKU( $sku )
 * @method static Builder onlyVariations()
 * @method static Builder excludeVariations()
 * @method static Builder withStockEnabled(): Product
 * @method static Builder withStockDisabled(): Product
 * @method static Builder accurateTracking( $argument = true ): Product
 * @method static Builder searchable( $attribute = true ): Product
 * @method static Builder type( $type ): Product
 * @method static Builder notGrouped(): Product
 * @method static Builder grouped(): Product
 * @method static Builder isGroup(): Product
 * @method static Builder notInGroup(): Product
 * @method static Builder inGroup(): Product
 */
class Product extends NsModel
{
    use HasFactory;

    const STOCK_MANAGEMENT_ENABLED = 'enabled';

    const STOCK_MANAGEMENT_DISABLED = 'disabled';

    const EXPIRES_PREVENT_SALES = 'prevent_sales';

    const EXPIRES_ALLOW_SALES = 'allow_sales';

    const TYPE_MATERIALIZED = 'materialized';

    const TYPE_DEMATERIALIZED = 'dematerialized';

    const TYPE_GROUPED = 'grouped';

    const STATUS_AVAILABLE = 'available';

    const STATUS_UNAVAILABLE = 'unavailable';

    protected $table = 'nexopos_' . 'products';

    protected $casts = [
        'accurate_tracking' => 'boolean',
        'auto_cogs' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'deleting' => ProductBeforeDeleteEvent::class,
        'deleted' => ProductAfterDeleteEvent::class,
    ];

    /**
     * Lock the resource from deletion if
     * it's a dependency for specified models.
     */
    protected $isDependencyFor = [
        OrderProduct::class => [
            'local_index' => 'id',
            'local_name' => 'name',
            'foreign_index' => 'product_id',
            'foreign_name' => [ Order::class, 'order_id', 'id', 'code' ],
        ],
    ];

    public function category()
    {
        return $this->belongsTo( ProductCategory::class, 'category_id', 'id' );
    }

    /**
     * get products having accurate tracking enabled
     *
     * @param Builder
     * @return Builder
     */
    public function scopeTrackingEnabled( $query )
    {
        return $query->where( 'accurate_tracking', true );
    }

    /**
     * get products having accurate tracking enabled
     *
     * @param Builder
     * @return Builder
     */
    public function scopeType( $query, $type )
    {
        return $query->where( 'type', $type );
    }

    /**
     * Add a scope that filter product
     * that aren't grouped
     */
    public function scopeNotGrouped( Builder $query )
    {
        return $query->where( 'type', '!=', self::TYPE_GROUPED );
    }

    /**
     * Filter products if they are grouped products.
     */
    public function scopeGrouped( Builder $query )
    {
        return $query->where( 'type', self::TYPE_GROUPED );
    }

    /**
     * Filter products if they are grouped products.
     *
     * @alias scopeGrouped
     */
    public function scopeIsGroup( Builder $query )
    {
        return $query->where( 'type', self::TYPE_GROUPED );
    }

    /**
     * Filter product that doesn't
     * belong to a group
     */
    public function scopeNotInGroup( Builder $query )
    {
        $subItemsIds = ProductSubItem::get( 'id' )->map( fn( $entry ) => $entry->id )->toArray();

        return $query->whereNotIn( 'id', $subItemsIds );
    }

    /**
     * Filter products that are
     * included as a sub_items.
     */
    public function scopeInGroup( Builder $query )
    {
        $subItemsIds = ProductSubItem::get( 'id' )->map( fn( $entry ) => $entry->id )->toArray();

        return $query->whereIn( 'id', $subItemsIds );
    }

    /**
     * get products having accurate tracking disabled
     *
     * @param Builder
     * @return Builder
     */
    public function scopeTrackingDisabled( $query )
    {
        return $query->where( 'accurate_tracking', false );
    }

    /**
     * get a product using a barcode
     *
     * @param Builder
     * @param string barcode
     * @return Builder
     */
    public function scopeFindUsingBarcode( $query, $barcode )
    {
        return $query->where( 'barcode', $barcode );
    }

    /**
     * get a product using a barcode
     *
     * @param Builder
     * @param string barcode
     * @return Builder
     */
    public function scopeBarcode( $query, $barcode )
    {
        return $this->scopeFindUsingBarcode( $query, $barcode );
    }

    /**
     * get a product using a barcode
     *
     * @param  Builder $query
     * @param  string  $sku
     * @return Builder
     */
    public function scopeSku( $query, $sku )
    {
        return $this->scopeFindUsingSKU( $query, $sku );
    }

    /**
     * get products that are on sale.
     *
     * @param Builder
     * @return Builder
     */
    public function scopeOnSale( $query )
    {
        return $query->where( 'status', self::STATUS_AVAILABLE );
    }

    /**
     * get products that aren't on sale.
     *
     * @param Builder
     * @return Builder
     */
    public function scopeHidden( $query )
    {
        return $query->where( 'status', self::STATUS_UNAVAILABLE );
    }

    /**
     * Filter a product using the SKU
     *
     * @param Builder
     * @param string sku
     * @return Builder
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

    public function sub_items()
    {
        return $this->hasMany( ProductSubItem::class, 'parent_id', 'id' );
    }

    public function history()
    {
        return $this->hasMany( ProductHistory::class, 'product_id', 'id' );
    }

    /**
     * Filter query by getting products that are variations
     *
     * @param Builder $query
     * @return Builder;
     */
    public function scopeOnlyVariations( $query )
    {
        return $query->where( 'product_type', 'variation' );
    }

    /**
     * Filter query by getting products that aren't variations
     *
     * @param Builder $query
     * @return Builder;
     */
    public function scopeExcludeVariations( $query )
    {
        return $query->where( 'product_type', '!=', 'variation' );
    }

    /**
     * Filter query by getting product with
     * stock management enabled
     *
     * @param Builder $query
     * @return Builder;
     */
    public function scopeWithStockEnabled( $query )
    {
        return $query->where( 'stock_management', Product::STOCK_MANAGEMENT_ENABLED );
    }

    /**
     * Filter query by getting product with
     * stock management disabled
     *
     * @param Builder $query
     * @return Builder;
     */
    public function scopeWithStockDisabled( $query )
    {
        return $query->where( 'stock_management', Product::STOCK_MANAGEMENT_DISABLED );
    }

    /**
     * Filter query by getitng product with
     * accurate stock enabled or not.
     *
     * @param  Builder $query
     * @return Builder
     */
    public function scopeAccurateTracking( $query, $argument = true )
    {
        return $query->where( 'accurate_tracking', $argument );
    }

    /**
     * Filter product that are searchable
     *
     * @param Builder
     * @return Builder
     */
    public function scopeSearchable( $query, $attribute = true )
    {
        return $query->where( 'searchable', $attribute );
    }
}
