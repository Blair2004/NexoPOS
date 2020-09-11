<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\ProcurementProduct;

class ProcurementBeforeUpdateProductEvent
{
    use SerializesModels;
    public $product;
    public $fields;

    public function __construct( ProcurementProduct $product, $fields )
    {
        $this->product  =   $product;
        $this->fields   =   $fields;
    }
}