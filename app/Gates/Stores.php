<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class Stores
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.stores' );
    }
}