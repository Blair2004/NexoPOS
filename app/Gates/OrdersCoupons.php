<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class OrdersCoupons
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.orders-coupons' );
    }
}