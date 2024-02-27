<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int            $id
 * @property int            $author
 * @property string         $uuid
 * @property \Carbon\Carbon $updated_at
 */
class CustomerAddress extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'customers_addresses';

    /**
     * define the relationship
     *
     * @return Model\RelationShip
     */
    public function groups()
    {
        return $this->belongsTo( Customer::class, 'customer_id' );
    }

    public function scopeFrom( $query, $id, $type )
    {
        return $query->where( 'customer_id', $id )
            ->where( 'type', $type );
    }
}
