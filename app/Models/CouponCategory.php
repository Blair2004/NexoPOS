<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $category_id
 */
class CouponCategory extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'coupons_categories';

    public $timestamps = false;

    public function coupon()
    {
        return $this->belongsTo( Coupon::class, 'coupon_id' );
    }

    public function category()
    {
        return $this->belongsTo( ProductCategory::class, 'category_id' );
    }
}
