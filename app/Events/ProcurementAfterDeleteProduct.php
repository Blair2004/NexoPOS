<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\ProcurementProduct;

class ProcurementAfterDeleteProduct
{
    use SerializesModels;

    public $product_id;
    public $procurement_id;

    public function __construct( $product_id, $procurement_id )
    {
        $this->product_id       =   $product_id;
        $this->procurement_id   =   $procurement_id;
    }
}