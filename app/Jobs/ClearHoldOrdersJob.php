<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Role;
use App\Services\DateService;
use App\Services\NotificationService;
use App\Services\Options;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClearHoldOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * @todo refactor to OrdersService
     * @return void
     */
    public function handle()
    {
        /**
         * @var Options
         */
        $options        =   app()->make( Options::class );

        /**
         * @var DateService
         */
        $date           =   app()->make( DateService::class );

        /**
         * @var NotificationService;
         */
        $notification   =   app()->make( NotificationService::class );

        $deleted        =   Order::paymentStatus( Order::PAYMENT_HOLD )
            ->get()
            ->filter( function( $order ) use ( $options, $date ) {
                /**
                 * @var Carbon
                 */
                $expectedDate   =   Carbon::parse( $order->created_at )
                    ->addDays( $options->get( 'ns_orders_quotation_expiration', 5 ) );

                if ( $expectedDate->lessThan( $date->now() ) ) {
                    /**
                     * @todo we might consider soft deleting for now
                     */
                    $order->delete();
                    return true;
                }
                return false;
            });

        if ( $deleted->count() > 0 ) {
            /**
             * Dispatch notification
             * to let admins know it has been cleared.
             */
            $notification->create([
                'title'         =>  __( 'Hold Order Cleared' ),
                'identifier'    =>  self::class,
                'description'   =>  sprintf( __( '%s order(s) has recently been deleted as they has expired.' ), $deleted->count() )
            ])->dispatchForGroup([
                Role::namespace( 'admin' ),
                Role::namespace( 'nexopos.store.administrator' ),
            ]);
        }
    }
}
