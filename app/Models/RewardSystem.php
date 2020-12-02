<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RewardSystem extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'rewards_system';

    public function rules()
    {
        return $this->hasMany( RewardSystemRule::class, 'reward_id' );
    }
}