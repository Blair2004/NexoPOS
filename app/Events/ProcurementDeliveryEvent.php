<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\Procurement;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;

class ProcurementDeliveryEvent 
{
    use SerializesModels;

    public $procurement;

    public function __construct( Procurement $procurement )
    {
        $this->procurement  =   $procurement;
    }
}