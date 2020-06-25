<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use Tendoo\Core\Exceptions\CoreException;

use App\Models\ProductCategory;

class AuthController extends Controller
{
    public function signIn()
    {
        return view( 'pages.sign-in' );
    }

    public function signUp()
    {
        return view( 'pages.sign-up' );
    }

    public function passwordLost()
    {
        return view( 'pages.password-lost' );
    }

    public function newPassword()
    {
        return view( 'pages.new-password' );
    }
}

