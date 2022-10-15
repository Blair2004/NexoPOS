<?php

namespace App\Events;

use App\Models\Procurement;
use Illuminate\Queue\SerializesModels;

class ProcurementDeliveryEvent
{
    use SerializesModels;

    public function __construct( public Procurement $procurement )
    {
        // ...
    }
}
