<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class ProcurementProduct
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.procurements-products' );
    }
}