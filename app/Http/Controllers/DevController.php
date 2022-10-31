<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DevController extends DashboardController
{
    public function index()
    {
        return $this->view( 'dev.index' );
    }
}
