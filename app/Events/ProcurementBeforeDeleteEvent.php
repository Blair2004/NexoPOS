<?php

namespace App\Events;

use App\Models\Procurement;
use Illuminate\Queue\SerializesModels;

class ProcurementBeforeDeleteEvent
{
    use SerializesModels;

    public $procurement;

    public function __construct( Procurement $procurement )
    {
        $this->procurement = $procurement;
    }
}
