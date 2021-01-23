<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends NsModel
{
    use HasFactory;

    protected $table    =   'nexopos_' . 'coupons';

    public function categories()
    {
        return $this->hasMany( CouponCategory::class );
    }

    public function products()
    {
        return $this->hasMany( CouponProduct::class );
    }
}
