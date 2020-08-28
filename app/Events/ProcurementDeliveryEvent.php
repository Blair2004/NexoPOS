<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\Procurement;

class ProcurementDeliveryEvent 
{
    use SerializesModels;

    public $procurement;

    public function __construct( Procurement $procurement )
    {
        $this->procurement  =   $procurement;
    }
}