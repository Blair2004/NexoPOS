<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property integer $reward_id
 * @property string $reward_name
 * @property float $target
 * @property \Carbon\Carbon $updated_at
*/
class CustomerReward extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'customers_rewards';

    public function reward()
    {
        return $this->belongsTo(
            related: RewardSystem::class,
            foreignKey: 'reward_id',
            ownerKey: 'id'
        );
    }
}
