<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $coupon_id
 * @property integer $group_id
*/
class CouponCustomerGroup extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table   =   'nexopos_' . 'coupons_customers_groups';

    public function coupon()
    {
        return $this->belongsTo( Coupon::class );
    }

    public function group()
    {
        return $this->hasOne( CustomerGroup::class );
    }
}
