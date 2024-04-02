<?php

namespace App\Models;

use App\Events\ProcurementProductAfterCreateEvent;
use App\Events\ProcurementProductAfterDeleteEvent;
use App\Events\ProcurementProductAfterUpdateEvent;
use App\Events\ProcurementProductBeforeCreateEvent;
use App\Events\ProcurementProductBeforeDeleteEvent;
use App\Events\ProcurementProductBeforeUpdateEvent;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property mixed          $name
 * @property float          $gross_purchase_price
 * @property float          $net_purchase_price
 * @property int            $procurement_id
 * @property int            $product_id
 * @property float          $purchase_price
 * @property float          $quantity
 * @property float          $available_quantity
 * @property int            $tax_group_id
 * @property mixed          $barcode
 * @property \Carbon\Carbon $expiration_date
 * @property mixed          $tax_type
 * @property float          $tax_value
 * @property float          $total_purchase_price
 * @property int            $unit_id
 * @property int            $convert_unit_id
 * @property bool           $visible
 * @property float          $cogs
 * @property int            $author
 * @property mixed          $uuid
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProcurementProduct extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'procurements_products';

    const STOCK_INCREASE = 'increase';

    const STOCK_REDUCE = 'reduce';

    protected $dispatchesEvents = [
        'creating' => ProcurementProductBeforeCreateEvent::class,
        'created' => ProcurementProductAfterCreateEvent::class,
        'deleting' => ProcurementProductBeforeDeleteEvent::class,
        'updating' => ProcurementProductBeforeUpdateEvent::class,
        'updated' => ProcurementProductAfterUpdateEvent::class,
        'deleted' => ProcurementProductAfterDeleteEvent::class,
    ];

    public function procurement()
    {
        return $this->belongsTo( Procurement::class, 'procurement_id' );
    }

    public function product()
    {
        return $this->hasOne( Product::class, 'id', 'product_id' );
    }

    public function unit()
    {
        return $this->hasOne( Unit::class, 'id', 'unit_id' );
    }

    /**
     * filter the procurement product
     * by using a procurement id as a pivot
     *
     * @param Query
     * @param string
     * @return Query;
     */
    public function scopeGetByProcurement( $query, $param )
    {
        return $query->where( 'procurement_id', $param );
    }

    /**
     * Fetch product from a procurement
     * using as specific barcode
     *
     * @param  QueryBuilder $query
     * @param  string       $barcode
     * @return QueryBuilder
     */
    public function scopeBarcode( $query, $barcode )
    {
        return $query->where( 'barcode', $barcode );
    }
}
