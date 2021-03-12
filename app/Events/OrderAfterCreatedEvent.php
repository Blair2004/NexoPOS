<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Queue\SerializesModels;

class OrderAfterCreatedEvent
{
    use SerializesModels;

    public $order;
    public $fields;

    public function __construct( Order $order, $fields )
    {
        $this->order    =   $order;
        $this->fields   =   $fields;
    }
}