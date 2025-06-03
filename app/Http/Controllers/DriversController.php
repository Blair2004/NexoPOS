<?php

namespace App\Http\Controllers;

use App\Crud\DriverCrud;
use App\Crud\DriverOrderCrud;
use App\Enums\DriverStatusEnum;
use App\Models\Driver;
use App\Models\DriverStatus;
use App\Models\Order;
use App\Models\OrderDeliveryProof;
use App\Classes\JsonResponse;
use Illuminate\Support\Carbon;
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

    public function editDriver( Driver $driver )
    {
        return DriverCrud::form( $driver );
    }

    public function getDriverOrders( Driver $driver )
    {
        return DriverOrderCrud::table([
            'queryParams' => [ 'driver_id' => $driver->id ],
        ]);
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

    public function getDrivers()
    {
        return Driver::with([ 'billing', 'shipping', 'attribute' ])->get();
    }

    public function updateOrder( Order $order )
    {
        $data = request()->validate([
            'is_delivered'      => 'required|boolean',
            'delivery_proof'    => 'nullable|string',
            'paid_on_delivery'  => 'required|boolean',
            'payment_method'    => 'nullable|string',
            'note'              => 'nullable|string',
        ]);

        $proof = OrderDeliveryProof::firstOrNew([
            'order_id'  => $order->id,
            'driver_id' => auth()->id(),
        ]);

        $proof->fill($data);
        $proof->delivered_at = Carbon::now();
        $proof->save();

        if ( $data['is_delivered'] ) {
            $order->delivery_status = Order::DELIVERY_DELIVERED;
            $order->save();
        }

        return JsonResponse::success(
            message: __( 'The order has been updated.' ),
            data: [
                'order' => $order,
                'proof' => $proof,
            ]
        );
    }
}
