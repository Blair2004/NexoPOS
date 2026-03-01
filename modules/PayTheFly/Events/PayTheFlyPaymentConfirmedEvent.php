<?php

namespace Modules\PayTheFly\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\PayTheFly\Models\PayTheFlyTransaction;

/**
 * Fired when a PayTheFly webhook confirms a payment on-chain.
 * Other modules/listeners can hook into this to trigger custom logic
 * (e.g. sending a receipt, updating inventory, notifications).
 */
class PayTheFlyPaymentConfirmedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public PayTheFlyTransaction $transaction,
    ) {
        //
    }
}
