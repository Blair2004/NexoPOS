<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class Registers
{
    public function registers()
    {
        return Auth::user()->allowedTo( 'nexopos.create.registers' );
    }
}