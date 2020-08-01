<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardSystem extends Model
{
    protected $table    =   'nexopos_' . 'rewards_system';

    public function rules()
    {
        return $this->hasMany( RewardSystemRule::class, 'reward_id' );
    }
}