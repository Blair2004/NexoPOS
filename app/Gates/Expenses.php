<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class Expenses
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.expenses' );
    }
}