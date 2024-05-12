<?php

namespace App\Listeners;

use App\Events\ProcurementBeforeCreateEvent;
use App\Exceptions\NotAllowedException;
use App\Models\Product;

class ProcurementBeforeCreateEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( ProcurementBeforeCreateEvent $event ): void
    {
        /**
         * we need to ensure we're not sending any grouped products
         * which aren't supported for procurement.
         */
        foreach ( $event->data[ 'products' ] as $procurementProduct ) {
            $product = Product::find( $procurementProduct[ 'product_id' ] );

            if ( $product->type === Product::TYPE_GROUPED ) {
                throw new NotAllowedException(
                    sprintf(
                        __( 'The product "%s" can\'t be procured as it\'s a grouped product.' ),
                        $product->name
                    )
                );
            }
        }
    }
}
