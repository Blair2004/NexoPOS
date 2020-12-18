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

    /**
     * Get the defined date format
     * @param string $date
     * @param string $mode
     * @return string formatted string
     */
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

    /**
     * Get the actual date time instance
     * based on the provided timezone
     * @return DateService $date
     */
    public function getNow()
    {
        return $this->now( $this->timezone );
    }

    /**
     * Return a formatted string the current date/time
     * usign a defined format (full or short)
     * @return string
     */
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