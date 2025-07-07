<?php

namespace App\Http\Controllers;

use App\Crud\DriverCrud;
use App\Enums\DriverStatusEnum;
use App\Models\Driver;
use App\Models\DriverStatus;
use App\Models\Order;
use App\Services\DriverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function getDrivers()
    {
        return Driver::with([ 'billing', 'shipping', 'attribute' ])->get();
    }

    /**
     * Get the 10 most recent deliveries assigned to the driver.
     */
    public function latestDeliveries(Driver $driver)
    {
        $deliveries = Order::where('driver_id', $driver->id)
            ->with( 'customer' )
            ->where('type', 'delivery')
            ->where( 'delivery_status', Order::DELIVERY_PENDING )
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        return response()->json($deliveries);
    }

    public function updateOrder( Order $order, Request $request )
    {
        $request->validate([
            'is_delivered' => 'required|boolean',
            'delivery_proof' => 'required|string',
            'note' => 'required|string',
            'payment_method' => 'nullable|string|exists:nexopos_payments_types,identifier',
            'paid_on_delivery' => 'nullable|boolean',
            'driver_id' => 'nullable|integer|exists:nexopos_users,id',
        ]);

        $data = $request->only([
            'is_delivered',
            'delivery_proof', 
            'note',
            'payment_method',
            'paid_on_delivery',
            'driver_id'
        ]);

        return $this->driverService->updateOrder($order, $data);
    }

    /**
     * Start a delivery by changing status from pending to ongoing
     */
    public function startDelivery(Order $order, Request $request)
    {
        // Get the authenticated driver's ID (you might need to adjust this based on your auth system)
        $driverId = Auth::id(); // or however you get the current driver's ID

        return $this->driverService->startDelivery($order, $driverId);
    }

    /**
     * Reject a delivery by unassigning driver
     */
    public function rejectDelivery(Order $order, Request $request)
    {
        // Get the authenticated driver's ID (you might need to adjust this based on your auth system)
        $driverId = Auth::id(); // or however you get the current driver's ID

        return $this->driverService->rejectDelivery($order, $driverId);
    }

    /**
     * Get driver earnings statistics for the authenticated driver
     */
    public function getDriverEarningsStats(Request $request)
    {
        // Get the authenticated driver's ID 
        $driverId = Auth::id();
        
        // Ensure the user is a driver
        $driver = Driver::find($driverId);
        if (!$driver) {
            return response()->json([
                'status' => 'error',
                'message' => __('Driver not found.')
            ], 404);
        }

        try {
            $stats = $this->driverService->getDriverStats($driver);
            
            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
