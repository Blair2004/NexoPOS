<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class Suppliers
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.suppliers' );
    }
}