<?php

namespace App\Events;

use App\Models\Procurement;
use Illuminate\Queue\SerializesModels;

class ProcurementDeletionEvent
{
    use SerializesModels;

    public $procurement;

    public function __construct( Procurement $procurement )
    {
        $this->procurement = $procurement;
    }
}
