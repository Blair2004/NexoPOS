<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $product_id
 * @property string $type
 * @property string $preview_url
 * @property string $expiration_date
 * @property int $unit_id
 * @property string $barcode
 * @property float $quantity
 * @property float $low_quantity
 * @property bool $stock_alert_enabled
 * @property float $sale_price
 * @property float $sale_price_edit
 * @property float $gross_sale_price
 * @property float $net_sale_price
 * @property float $sale_price_tax
 * @property float $wholesale_price
 * @property float $wholesale_price_edit
 * @property float $net_wholesale_price
 * @property float $gross_wholesale_price
 * @property float $wholesale_price_tax
 * @property float $custom_price
 * @property float $custom_price_edit
 * @property float $net_custom_price
 * @property float $gross_custom_price
 */
class ProductUnitQuantity extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'products_unit_quantities';

    /**
     * Fetch products unique a barcode filter
     *
     * @param QueryBuilder $query
     * @param string $reference
     * @return QueryBuilder
     **/
    public function scopeBarcode( $query, $reference )
    {
        return $query->where( 'barcode', $reference );
    }

    public function unit()
    {
        return $this->hasOne( Unit::class, 'id', 'unit_id' );
    }

    public function taxes()
    {
        return $this->hasMany( ProductTax::class, 'unit_quantity_id' );
    }

    public function scopeWithUnit( Builder $query, $id )
    {
        return $query->where( 'unit_id', $id );
    }

    public function product()
    {
        return $this->hasOne( Product::class, 'id', 'product_id' );
    }

    public function scopeWithProduct( Builder $query, $id )
    {
        return $query->where( 'product_id', $id );
    }

    public function scopeStockAlertEnabled( Builder $query )
    {
        return $query->where( 'stock_alert_enabled', true );
    }
}
