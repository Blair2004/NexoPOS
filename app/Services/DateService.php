<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DateService extends Carbon
{
    public function __construct( $time = 'now', $timeZone = 'Europe/London' )
    {
        parent::__construct( $time, $timeZone );
    }
}