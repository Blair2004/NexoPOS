<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;

class TransfersController extends DashboardController
{
    /**
     * Index Controller Page
     * @return  view
     * @since  1.0
    **/
    public function index()
    {
        return View::make( 'NexoPOS::index' );
    }
}

