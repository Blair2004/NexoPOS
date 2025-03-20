<?php
namespace App\Services;

use App\Classes\JsonResponse;
use App\Enums\DriverStatusEnum;
use App\Models\Driver;
use App\Models\DriverStatus;

class DriverService
{
    /**
     * Will change the driver status
     * @param Driver $driver
     * @param $status
     * @return JsonResponse
     */
    public function changeStatus( Driver $driver, $status )
    {
        $driverStatus   =   $driver->status()->first();

        /**
         * The driver status hasn't been created? We should
         * create it before setting any status.
         */
        if ( ! $status instanceof DriverStatus ) {
            $driverStatus   =   new DriverStatus;
        }

        $driverStatus->driver_id = $driver->id;
        $driverStatus->status = $status;
        $driverStatus->save();

        return JsonResponse::success(
            message: __( 'The driver status has been updated.' ),
            data: compact( 'driver', 'driverStatus' )
        );
    }

    public function getByStatus( $status )
    {
        return Driver::whereHas( 'status', function( $query ) use ( $status ) {
            $query->where( 'status', $status );
        })->get();
    }
}