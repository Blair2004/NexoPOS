<?php

namespace App\Http\Controllers;

use App\Crud\DriverCrud;
use Illuminate\Http\Request;

class DriversController extends Controller
{
    public function listDrivers()
    {
        return DriverCrud::table();
    }

    public function createDriver()
    {
        return DriverCrud::form();
    }

    public function editDriver()
    {
        // ..
    }

    public function getDriverOrders()
    {
        // ..
    }

}
