<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property int            $coupon_id
 * @property string         $uuid
 * @property float          $target
 * @property string         $description
 * @property \Carbon\Carbon $updated_at
 */
class RewardSystem extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'rewards_system';

    public function rules()
    {
        return $this->hasMany( RewardSystemRule::class, 'reward_id' );
    }

    public function coupon()
    {
        return $this->hasOne( Coupon::class, 'id', 'coupon_id' );
    }
}
