<?php

namespace App\Models;

use App\Classes\Model as ClassesModel;
use App\Events\CustomerModelBootedEvent;
use App\Traits\NsCustomerAddress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string first_name
 * @property string last_name
 * @property string description
 * @property int author_id
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
    use HasFactory, NsCustomerAddress;

    protected $table = 'nexopos_' . 'users';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function setDependencies()
    {
        return [
            Order::class => ClassesModel::dependant(
                local_name: 'name',
                local_index: 'id',
                foreign_name: 'code',
                foreign_index: 'customer_id',
            ),
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope( 'customers', function ( Builder $builder ) {
            $customerRoleIds = Role::query()
                ->select( 'id' )
                ->where( 'namespace', Role::STORECUSTOMER );
            $administratorRoleIds = Role::query()
                ->select( 'id' )
                ->where( 'namespace', Role::ADMIN );

            $builder
                ->whereIn( 'nexopos_users.id', UserRoleRelation::query()
                    ->select( 'user_id' )
                    ->whereIn( 'role_id', $customerRoleIds ) )
                ->whereNotIn( 'nexopos_users.id', UserRoleRelation::query()
                    ->select( 'user_id' )
                    ->whereIn( 'role_id', $administratorRoleIds ) );

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

    public function account_history()
    {
        return $this->hasMany(
            related: CustomerAccountHistory::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }
}
