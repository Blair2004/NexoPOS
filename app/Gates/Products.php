<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class Products
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.products' );
    }
}