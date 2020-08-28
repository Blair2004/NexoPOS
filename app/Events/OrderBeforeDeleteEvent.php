<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Queue\SerializesModels;

class OrderBeforeDeleteEvent 
{
    use SerializesModels;
    public $order;

    public function __construct( Order $order )
    {
        $this->order    =   $order;
    }
}