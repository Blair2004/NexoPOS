<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string name
 * @property string surname
 * @property string description
 * @property int author
 * @property string gender
 * @property string phone
 * @property string email
 * @property string pobox
 * @property int group_id
 * @property string birth_date
 * @property float purchases_amount
 * @property float owed_amount
 * @property float credit_limit_amount
 * @property float account_amount
 */
class Customer extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'customers';

    protected $isDependencyFor = [
        Order::class => [
            'local_name' => 'name',
            'local_index' => 'id',
            'foreign_name' => 'code',
            'foreign_index' => 'customer_id',
        ],
    ];

    /**
     * define the relationship
     *
     * @return Model\RelationShip
     */
    public function group()
    {
        return $this->belongsTo( CustomerGroup::class, 'group_id' );
    }

    public function coupons()
    {
        return $this->hasMany( CustomerCoupon::class, 'customer_id' );
    }

    public function rewards()
    {
        return $this->hasMany( CustomerReward::class, 'customer_id' );
    }

    /**
     * define the relationship
     *
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

    public function account_history()
    {
        return $this->hasMany( CustomerAccountHistory::class, 'customer_id' );
    }

    /**
     * Get customer using email
     *
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
