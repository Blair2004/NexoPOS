<?php
namespace App\Models;

use App\Events\ProcurementProductAfterCreateEvent;
use App\Events\ProcurementProductAfterDeleteEvent;
use App\Events\ProcurementProductAfterUpdateEvent;
use App\Events\ProcurementProductBeforeCreateEvent;
use App\Events\ProcurementProductBeforeDeleteEvent;
use App\Events\ProcurementProductBeforeUpdateEvent;
use Illuminate\Database\Eloquent\Model;

class ProcurementProduct extends Model
{
    protected $table    =   'nexopos_' . 'procurements_products';

    protected $dispatchesEvents     =   [
        'creating'      =>  ProcurementProductBeforeCreateEvent::class,
        'created'       =>  ProcurementProductAfterCreateEvent::class,
        'deleting'      =>  ProcurementProductBeforeDeleteEvent::class,
        'updating'      =>  ProcurementProductBeforeUpdateEvent::class,
        'updated'       =>  ProcurementProductAfterUpdateEvent::class,
        'deleted'       =>  ProcurementProductAfterDeleteEvent::class,
    ];

    public function procurement()
    {
        return $this->belongsTo( Procurement::class, 'procurement_id' );
    }

    public function unit()
    {
        return $this->hasOne( Unit::class, 'id', 'unit_id' );
    }

    /**
     * filter the procurement product
     * by using a procurement id as a pivot
     * @param Query
     * @param string
     * @return Query;
     */
    public function scopeGetByProcurement( $query, $param )
    {
        return $query->where( 'procurement_id', $param );
    }
}