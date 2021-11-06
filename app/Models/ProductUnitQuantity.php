<?php
namespace App\Models;

use App\Models\Unit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductUnitQuantity extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'products_unit_quantities';

    /**
     * Fetch products unique a barcode filter
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
