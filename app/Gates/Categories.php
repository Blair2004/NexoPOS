<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class Categories
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.categories' );
    }
}