<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerAddress extends NsModel
{
    use HasFactory;

    protected $table    =   'nexopos_' . 'customers_addresses';

    /**
     * define the relationship
     * @return Model\RelationShip
     */
    public function groups()
    {
        return $this->belongsTo( Customer::class, 'customer_id' );
    }

    public function scopefrom( $query, $id, $type )
    {
        return $query->where( 'customer_id', $id )
            ->where( 'type', $type );
    }
}