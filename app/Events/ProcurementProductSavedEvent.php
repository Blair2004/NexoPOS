<?php

namespace App\Events;

use App\Models\ProcurementProduct;
use Illuminate\Queue\SerializesModels;

class ProcurementProductSavedEvent
{
    use SerializesModels;

    public $product;

    public function __construct( ProcurementProduct $product )
    {
        $this->product = $product;
    }
}
