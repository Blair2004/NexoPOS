<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class StockTransfer
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.stock-transfer' );
    }
}