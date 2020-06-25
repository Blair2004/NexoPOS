<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementProduct extends Model
{
    protected $table    =   'nexopos_' . 'procurements_products';

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