<?php

namespace App\Events;

use App\Models\ProcurementProduct;
use Illuminate\Queue\SerializesModels;

class ProcurementBeforeDeleteProductEvent
{
    use SerializesModels;

    public function __construct( public ProcurementProduct $product )
    {
        // ...
    }
}
