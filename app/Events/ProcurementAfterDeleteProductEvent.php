<?php
namespace App\Events;

use App\Models\Procurement;
use Illuminate\Queue\SerializesModels;
use App\Models\ProcurementProduct;

class ProcurementAfterDeleteProductEvent
{
    use SerializesModels;

    public $product_id;
    public $procurement_id;

    public function __construct( $product_id, Procurement $procurement )
    {
        $this->product_id       =   $product_id;
        $this->procurement      =   $procurement;
    }
}