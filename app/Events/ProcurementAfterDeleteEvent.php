<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class ProcurementAfterDeleteEvent
{
    use SerializesModels;

    public function __construct( public $procurement_data )
    {
        // ...
    }
}
