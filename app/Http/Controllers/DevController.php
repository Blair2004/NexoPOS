<?php

namespace App\Http\Controllers;

class DevController extends DashboardController
{
    public function index()
    {
        return $this->view( 'dev.index' );
    }
}
