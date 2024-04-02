<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers;

use App\Classes\Hook;
use App\Classes\Output;
use App\Models\Customer;
use App\Models\DashboardDay;
use App\Models\Order;
use App\Models\Role;
use App\Services\DateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DateService $dateService
    ) {
        // ...
    }

    public function home()
    {
        return View::make( 'pages.dashboard.home', [
            'title' => __( 'Dashboard' ),
        ] );
    }

    /**
     * @deprecated
     */
    protected function view( $path, $data = [] )
    {
        return view( $path, $data );
    }

    public function getCards()
    {
        $todayStarts = $this->dateService->copy()->startOfDay()->toDateTimeString();
        $todayEnds = $this->dateService->copy()->endOfDay()->toDateTimeString();

        return DashboardDay::from( $todayStarts )
            ->to( $todayEnds )
            ->first() ?: [];
    }

    public function getBestCustomers()
    {
        return Customer::orderBy( 'purchases_amount', 'desc' )->limit( 5 )->get();
    }

    public function getRecentsOrders()
    {
        return Order::orderBy( 'created_at', 'desc' )->with( 'user' )->limit( 10 )->get();
    }

    public function getBestCashiers()
    {
        return Role::namespace( 'nexopos.store.cashier' )
            ->users()
            ->orderBy( 'total_sales', 'desc' )
            ->limit( 10 )
            ->get();
    }

    /**
     * Will create a hook that will inject
     * Output object on the footer. Useful to create
     * custom output per page.
     *
     * @param  string $name
     * @return void
     */
    public function hookOutput( $name )
    {
        Hook::addAction( 'ns-dashboard-footer', function ( Output $output ) use ( $name ) {
            Hook::action( $name, $output );
        }, 15 );
    }

    public function getWeekReports()
    {
        $weekMap = [
            0 => [
                'label' => __( 'Sunday' ),
                'value' => 'SU',
            ],
            1 => [
                'label' => __( 'Monday' ),
                'value' => 'MO',
            ],
            2 => [
                'label' => __( 'Tuesday' ),
                'value' => 'TU',
            ],
            3 => [
                'label' => __( 'Wednesday' ),
                'value' => 'WE',
            ],
            4 => [
                'label' => __( 'Thursday' ),
                'value' => 'TH',
            ],
            5 => [
                'label' => __( 'Friday' ),
                'value' => 'FR',
            ],
            6 => [
                'label' => __( 'Saturday' ),
                'value' => 'SA',
            ],
        ];

        $currentWeekStarts = $this->dateService->copy()->startOfWeek();
        $currentWeekEnds = $this->dateService->copy()->endOfWeek();
        $lastWeekStarts = $currentWeekStarts->copy()->subDay()->startOfWeek();
        $lastWeekEnds = $currentWeekStarts->copy()->subDay()->endOfWeek();

        DashboardDay::from( $currentWeekStarts->toDateTimeString() )
            ->to( $currentWeekEnds->toDateTimeString() )
            ->get()
            ->each( function ( $report ) use ( &$weekMap ) {
                if ( ! isset( $weekMap[ Carbon::parse( $report->range_starts )->dayOfWeek ][ 'current' ][ 'entries' ] ) ) {
                    $weekMap[ Carbon::parse( $report->range_starts )->dayOfWeek ][ 'current' ][ 'entries' ] = [];
                }

                $weekMap[ Carbon::parse( $report->range_starts )->dayOfWeek ][ 'current' ][ 'entries' ][] = $report;
            } );

        DashboardDay::from( $lastWeekStarts->toDateTimeString() )
            ->to( $lastWeekEnds->toDateTimeString() )
            ->get()
            ->each( function ( $report ) use ( &$weekMap ) {
                if ( ! isset( $weekMap[ Carbon::parse( $report->range_starts )->dayOfWeek ][ 'previous' ][ 'entries' ] ) ) {
                    $weekMap[ Carbon::parse( $report->range_starts )->dayOfWeek ][ 'previous' ][ 'entries' ] = [];
                }

                $weekMap[ Carbon::parse( $report->range_starts )->dayOfWeek ][ 'previous' ][ 'entries' ][] = $report;
            } );

        return [
            'range_starts' => $lastWeekStarts->toDateTimeString(),
            'range_ends' => $currentWeekEnds->toDateTimeString(),
            'result' => $weekMap,
        ];
    }
}
