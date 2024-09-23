<?php

namespace App\Models;

use App\Events\CustomerRewardAfterCreatedEvent;
use App\Events\CustomerRewardAfterUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property int            $reward_id
 * @property string         $reward_name
 * @property float          $target
 * @property \Carbon\Carbon $updated_at
 */
class CustomerReward extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'customers_rewards';

    public $dispatchesEvents = [
        'created' => CustomerRewardAfterCreatedEvent::class,
        'updated' => CustomerRewardAfterUpdatedEvent::class,
    ];

    public function customer()
    {
        return $this->belongsTo(
            related: Customer::class,
            foreignKey: 'customer_id',
            ownerKey: 'id'
        );
    }

    public function reward()
    {
        return $this->belongsTo(
            related: RewardSystem::class,
            foreignKey: 'reward_id',
            ownerKey: 'id'
        );
    }
}
