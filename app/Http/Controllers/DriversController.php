<?php

namespace App\Http\Controllers;

use App\Crud\DriverCrud;
use App\Enums\DriverStatusEnum;
use App\Models\Driver;
use App\Models\DriverStatus;
use App\Services\DriverService;
use Illuminate\Http\Request;

class DriversController extends Controller
{
    public function __construct( public DriverService $driverService )
    {
        // ...
    }

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

    public function changeStatus( Request $request, Driver $driver )
    {
        $status     =   match( $request->input( 'status' ) ) {
            'available'  =>  DriverStatusEnum::Available,
            'busy'       =>  DriverStatusEnum::Busy,
            'offline'    =>  DriverStatusEnum::Offline,
            'disabled'   =>  DriverStatusEnum::Disabled,
            default      =>  DriverStatusEnum::Disabled,
        };

        return $this->driverService->changeStatus(
            driver: $driver,
            status: $status
        );
    }

    public function getDriverByStatus( string $status )
    {
        return $this->driverService->getByStatus( $status );
    }
}
