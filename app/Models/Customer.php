<?php

namespace App\Models;

use App\Events\CustomerModelBootedEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property int id
 * @property string first_name
 * @property string last_name
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
class Customer extends UserScope
{
    use HasFactory;

    protected $table = 'nexopos_' . 'users';

    protected $isDependencyFor = [
        Order::class => [
            'local_name' => 'name',
            'local_index' => 'id',
            'foreign_name' => 'code',
            'foreign_index' => 'customer_id',
        ],
    ];

    protected static function booted()
    {
        static::addGlobalScope( 'customers', function ( Builder $builder ) {
            $role = DB::table( 'nexopos_roles' )->where( 'namespace', Role::STORECUSTOMER )->first();

            $userRoleRelations = DB::table( 'nexopos_users_roles_relations' )
                ->where( 'role_id', $role->id )
                ->get( [ 'user_id', 'role_id' ] );

            $builder->whereIn( 'id', $userRoleRelations->map( fn( $role ) => $role->user_id )->toArray() );

            CustomerModelBootedEvent::dispatch( $builder );
        } );
    }

    /**
     * define the relationship
     *
     * @return Model\RelationShip
     */
    public function group()
    {
        return $this->belongsTo(
            related: CustomerGroup::class,
            foreignKey: 'group_id',
            ownerKey: 'id'
        );
    }

    public function coupons()
    {
        return $this->hasMany(
            related: CustomerCoupon::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }

    public function rewards()
    {
        return $this->hasMany(
            related: CustomerReward::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }

    /**
     * define the relationship
     *
     * @return Model\RelationShip
     */
    public function orders()
    {
        return $this->hasMany(
            related: Order::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }

    public function addresses()
    {
        return $this->hasMany(
            related: CustomerAddress::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }

    public function billing()
    {
        return $this->hasOne(
            related: CustomerBillingAddress::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }

    public function shipping()
    {
        return $this->hasOne(
            related: CustomerShippingAddress::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }

    public function account_history()
    {
        return $this->hasMany(
            related: CustomerAccountHistory::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }
}
