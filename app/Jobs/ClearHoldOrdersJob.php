<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Role;
use App\Services\DateService;
use App\Services\NotificationService;
use App\Services\Options;
use App\Traits\NsSerialize;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ClearHoldOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @todo refactor to OrdersService
     *
     * @return void
     */
    public function handle(
        Options $options,
        DateService $date,
        NotificationService $notification
    ) {
        // we should prevent unpaid order to be deleted by default.
        if ( empty( $options->get( 'ns_orders_quotation_expiration', 'never' ) ) ) {
            return;
        }

        $deleted = Order::paymentStatus( Order::PAYMENT_HOLD )
            ->get()
            ->filter( function ( $order ) use ( $options, $date ) {
                /**
                 * @var Carbon
                 */
                $expectedDate = Carbon::parse( $order->created_at )
                    ->addDays( $options->get( 'ns_orders_quotation_expiration' ) );

                if ( $expectedDate->lessThan( $date->now() ) ) {
                    /**
                     * @todo we might consider soft deleting for now
                     */
                    $order->delete();

                    return true;
                }

                return false;
            } );

        if ( $deleted->count() > 0 ) {
            /**
             * Dispatch notification
             * to let admins know it has been cleared.
             */
            $notification->create( [
                'title' => __( 'Hold Order Cleared' ),
                'identifier' => self::class,
                'description' => sprintf( __( '%s order(s) has recently been deleted as they have expired.' ), $deleted->count() ),
            ] )->dispatchForGroup( [
                Role::namespace( 'admin' ),
                Role::namespace( 'nexopos.store.administrator' ),
            ] );
        }
    }
}
