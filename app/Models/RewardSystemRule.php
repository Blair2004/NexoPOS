<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property float          $reward
 * @property int            $author
 * @property string         $uuid
 * @property \Carbon\Carbon $updated_at
 */
class RewardSystemRule extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'rewards_system_rules';

    public function scopeAttachedTo( $query, $id )
    {
        return $query->where( 'reward_id', $id );
    }

    public function reward()
    {
        return $this->belongsTo( RewardSystem::class, 'reward_id' );
    }
}
