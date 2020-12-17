<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DateService extends Carbon
{
    private $options;
    private $timezone;

    public function __construct( $time = 'now', $timeZone = 'Europe/London' )
    {
        parent::__construct( $time, $timeZone );

        $this->timezone     =   $timeZone;        
        $this->options  =   app()->make( Options::class );
    }

    public function define( $time, $timeZone = 'Europe/London' )
    {
        $this->__construct( $time, $timeZone );
    }

    public function getFormatted( $date, $mode = 'full' )
    {
        switch( $mode ) {
            case 'short':
                return $this->parse( $date )->format( $this->options->get( 'ns_date_format', 'Y-m-d' ) );
            break;
            case 'full':
                return $this->parse( $date )->format( $this->options->get( 'ns_datetime_format', 'Y-m-d H:i:s' ) );
            break;
        }
    }

    public function getNowFormatted( $mode = 'full' )
    {
        switch( $mode ) {
            case 'short':
                return $this->now( $this->timezone )->format( $this->options->get( 'ns_date_format', 'Y-m-d' ) );
            break;
            case 'full':
                return $this->now( $this->timezone )->format( $this->options->get( 'ns_datetime_format', 'Y-m-d H:i:s' ) );
            break;
        }
    }
}