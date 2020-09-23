<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderCoupon extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'orders_coupons';
}