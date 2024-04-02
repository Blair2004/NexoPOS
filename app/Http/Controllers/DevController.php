<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\View;

class DevController extends DashboardController
{
    public function index()
    {
        return View::make( 'dev.index' );
    }
}
