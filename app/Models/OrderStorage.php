<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property int            $quantity
 * @property string         $session_identifier
 * @property \Carbon\Carbon $updated_at
 */
class OrderStorage extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'orders_storage';

    public function scopeWithIdentifier( $query, $id )
    {
        return $query->where( 'session_identifier', $id );
    }

    public function scopeWithProduct( $query, $id )
    {
        return $query->where( 'product_id', $id );
    }

    public function scopeWithUnitQuantity( $query, $id )
    {
        return $query->where( 'unit_quantity_id', $id );
    }
}
