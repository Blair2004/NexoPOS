<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class Customers
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.customers' );
    }
}