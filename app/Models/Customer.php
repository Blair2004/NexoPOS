<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'customers';

    /**
     * define the relationship
     * @return Model\RelationShip
     */
    public function group()
    {
        return $this->belongsTo( CustomerGroup::class, 'group_id' );
    }

    /**
     * define the relationship
     * @return Model\RelationShip
     */
    public function orders()
    {
        return $this->hasMany( Order::class, 'customer_id' );
    }

    public function addresses()
    {
        return $this->hasMany( CustomerAddress::class, 'customer_id' );
    }

    public function billing()
    {
        return $this->hasOne( CustomerBillingAddress::class, 'customer_id' );
    }

    public function shipping()
    {
        return $this->hasOne( CustomerShippingAddress::class, 'customer_id' );
    }

    /**
     * Get customer using email
     * @param Query
     * @param string email
     */
    public function scopeByEmail( $query, $email )
    {
        return $query->where( 'email', $email );
    }

    /**
     * get customers from groups
     */
    public function scopeFromGroup( $query, $index )
    {
        return $query->where( 'parent_id', $index );
    }
}