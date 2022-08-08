<?php

namespace App\Events;

use App\Models\Procurement;
use Illuminate\Queue\SerializesModels;

class ProcurementDeletionEvent
{
    use SerializesModels;

    public function __construct( public Procurement $procurement )
    {
        // ...
    }
}
