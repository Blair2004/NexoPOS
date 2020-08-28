<?php 
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\ProcurementProduct;

class ProcurementProductSavedEvent
{
    use SerializesModels;

    public $product;

    public function __construct( ProcurementProduct $product )
    {
        $this->product      =   $product;
    }
}