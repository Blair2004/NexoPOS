<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardSystemRule extends Model
{
    protected $table    =   'nexopos_' . 'rewards_system_rules';

    public function scopeAttachedTo( $query, $id )
    {
        return $query->where( 'reward_id', $id );
    }
}