<?php

use App\Models\CashFlow;
use App\Models\Order;
use App\Models\Role;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class WipeCashFlowTransaction2janv22 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $order          =   Order::first();
        
        if( $order instanceof Order ) {
            $fromDate       =   Carbon::parse( $order->created_at );
            $toDate         =   ns()->date->copy()->endOfDay();
            $wasLoggedIn    =   true;

            if ( ! Auth::check() ) {
                $wasLoggedIn        =   false;
                $user               =   Role::namespace( 'admin' )->users->first();
                Auth::login( $user );
            }
            
            /**
             * @var ReportService $reportService
             */
            $reportService      =   app()->make( ReportService::class );
            $reportService->recomputeCashFlow( $fromDate, $toDate );

            if ( ! $wasLoggedIn ) {
                Auth::logout();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
