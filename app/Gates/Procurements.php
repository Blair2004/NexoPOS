<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class Procurements
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.procurements' );
    }
}