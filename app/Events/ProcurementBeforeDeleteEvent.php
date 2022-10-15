<?php

namespace App\Events;

use App\Models\Procurement;
use Illuminate\Queue\SerializesModels;

class ProcurementBeforeDeleteEvent
{
    use SerializesModels;

    public function __construct( public Procurement $procurement )
    {
        // ...
    }
}
