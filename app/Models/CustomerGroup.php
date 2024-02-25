<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int            $id
 * @property string         $uuid
 * @property string         $description
 * @property int            $author
 * @property \Carbon\Carbon $updated_at
 */
class CustomerGroup extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'customers_groups';

    /**
     * define the relationship
     *
     * @return Model\RelationShip
     */
    public function customers()
    {
        return $this->hasMany( Customer::class, 'group_id' );
    }

    public function reward()
    {
        return $this->hasOne( RewardSystem::class, 'id', 'reward_system_id' );
    }
}
