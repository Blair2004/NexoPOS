<?php

namespace App\Events;

use App\Models\ProcurementProduct;
use Illuminate\Queue\SerializesModels;

class ProcurementBeforeUpdateProductEvent
{
    use SerializesModels;

    public $product;

    public $fields;

    public function __construct( ProcurementProduct $product, $fields )
    {
        $this->product = $product;
        $this->fields = $fields;
    }
}
