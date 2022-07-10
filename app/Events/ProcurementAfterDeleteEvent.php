<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class ProcurementAfterDeleteEvent
{
    use SerializesModels;

    public $procurement_data;

    public function __construct( $procurement_data )
    {
        $this->procurement_data = $procurement_data;
    }
}
