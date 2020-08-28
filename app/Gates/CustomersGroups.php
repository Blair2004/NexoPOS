<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class CustomersGroups
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.customers-groups' );
    }
}